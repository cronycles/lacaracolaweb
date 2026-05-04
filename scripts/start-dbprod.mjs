#!/usr/bin/env node

import { spawn } from "node:child_process";
import { copyFileSync, existsSync, readFileSync } from "node:fs";
import net from "node:net";
import os from "node:os";
import path from "node:path";
import { fileURLToPath } from "node:url";

const scriptDirectory = path.dirname(fileURLToPath(import.meta.url));
const projectRoot = path.resolve(scriptDirectory, "..");
const envFile = path.join(projectRoot, ".env.prod-local");
const localEnvFile = path.join(projectRoot, ".env.local");
const exampleEnvFile = path.join(projectRoot, ".env.example");
const isPrintOnly = process.argv.includes("--print");

const sshHost = process.env.DBPROD_SSH_HOST ?? "65.108.143.244";
const sshPort = process.env.DBPROD_SSH_PORT ?? "2299";
const sshUser = process.env.DBPROD_SSH_USER ?? "lacaraco";
const sshKey =
    process.env.DBPROD_SSH_KEY ??
    path.join(os.homedir(), ".ssh", "id_rsa_supporthost");
const tunnelLocalPort = process.env.DBPROD_TUNNEL_LOCAL_PORT ?? "3307";
const tunnelRemoteHost = process.env.DBPROD_TUNNEL_REMOTE_HOST ?? "localhost";
const tunnelRemotePort = process.env.DBPROD_TUNNEL_REMOTE_PORT ?? "3306";

if (!existsSync(envFile)) {
    console.error(`Missing required file: ${envFile}`);
    process.exit(1);
}

if (!existsSync(localEnvFile)) {
    if (!existsSync(exampleEnvFile)) {
        console.error(`Missing required bootstrap file: ${exampleEnvFile}`);
        process.exit(1);
    }

    copyFileSync(exampleEnvFile, localEnvFile);
    console.log("Created .env.local from .env.example.");
}

const tunnelArgs = [
    "-o",
    "ExitOnForwardFailure=yes",
    "-i",
    sshKey,
    "-p",
    sshPort,
    "-L",
    `${tunnelLocalPort}:${tunnelRemoteHost}:${tunnelRemotePort}`,
    `${sshUser}@${sshHost}`,
    "-N",
];

if (isPrintOnly) {
    console.log("Tunnel command:");
    console.log(["ssh", ...tunnelArgs].join(" "));
    console.log("");
    console.log("App environment source:");
    console.log(envFile);
    console.log("");
    console.log("Stack command:");
    console.log(
        'npx concurrently --kill-others-on-fail --names APP,VITE --prefix-colors green,cyan "php artisan serve --env=prod-local --host=127.0.0.1 --port=8000" "npm run dev -- --host 127.0.0.1 --port 5173"',
    );
    process.exit(0);
}

await startDbProd();

async function startDbProd() {
    console.log(
        `Opening SSH tunnel on 127.0.0.1:${tunnelLocalPort} -> remote MySQL ${tunnelRemoteHost}:${tunnelRemotePort}`,
    );
    console.log("Leave this terminal open. If asked, enter your SSH password.");

    const tunnel = spawn("ssh", tunnelArgs, {
        cwd: projectRoot,
        stdio: "inherit",
    });

    tunnel.on("error", (error) => {
        if (error.message.includes("ENOENT")) {
            console.error("Unable to start SSH tunnel: ssh command not found.");
            console.error(
                'Install OpenSSH client and ensure "ssh" is available in PATH.',
            );
        } else {
            console.error(`Unable to start SSH tunnel: ${error.message}`);
        }

        process.exit(1);
    });

    const tunnelExitPromise = new Promise((resolve) => {
        tunnel.on("exit", (code, signal) => {
            resolve({ code, signal });
        });
    });

    try {
        console.log(
            `Waiting for SSH tunnel on 127.0.0.1:${tunnelLocalPort}...`,
        );
        await waitForPort(
            "127.0.0.1",
            Number.parseInt(tunnelLocalPort, 10),
            60000,
        );
    } catch {
        terminateProcess(tunnel);
        console.error(
            `SSH tunnel did not become ready on 127.0.0.1:${tunnelLocalPort} within 60 seconds.`,
        );
        process.exit(1);
    }

    console.log("Tunnel is ready. Starting Laravel against production DB.");

    copyFileSync(envFile, path.join(projectRoot, ".env"));
    await runCommand("php", ["artisan", "optimize:clear"], {
        cwd: projectRoot,
    });

    const envOverrides = parseEnvFile(envFile);
    const stack = spawn(
        'npx concurrently --kill-others-on-fail --names APP,VITE --prefix-colors green,cyan "php artisan serve --env=prod-local --host=127.0.0.1 --port=8000" "npm run dev -- --host 127.0.0.1 --port 5173"',
        [],
        {
            cwd: projectRoot,
            env: {
                ...process.env,
                ...envOverrides,
            },
            stdio: "inherit",
            shell: true,
        },
    );

    const shutdown = () => {
        terminateProcess(stack);
        terminateProcess(tunnel);
    };

    process.once("SIGINT", shutdown);
    process.once("SIGTERM", shutdown);

    const stackExitPromise = new Promise((resolve) => {
        stack.on("exit", (code, signal) => {
            resolve({ code, signal });
        });
    });

    const firstExit = await Promise.race([
        stackExitPromise.then((result) => ({ source: "stack", ...result })),
        tunnelExitPromise.then((result) => ({ source: "tunnel", ...result })),
    ]);

    if (firstExit.source === "tunnel") {
        terminateProcess(stack);

        if (firstExit.code !== 0) {
            console.error(
                `SSH tunnel exited unexpectedly (code: ${firstExit.code ?? "unknown"}).`,
            );
        }

        process.exit((firstExit.code ?? 1) === 0 ? 1 : (firstExit.code ?? 1));
    }

    terminateProcess(tunnel);
    process.exit(firstExit.code ?? 0);
}

function terminateProcess(childProcess) {
    if (!childProcess || childProcess.killed) {
        return;
    }

    childProcess.kill("SIGTERM");
}

function runCommand(command, args, options) {
    return new Promise((resolve, reject) => {
        const child = spawn(command, args, {
            ...options,
            stdio: "inherit",
            shell: process.platform === "win32",
        });

        child.on("error", reject);
        child.on("exit", (code) => {
            if (code === 0) {
                resolve();
                return;
            }

            reject(
                new Error(
                    `${command} ${args.join(" ")} failed with code ${code ?? 1}`,
                ),
            );
        });
    });
}

function parseEnvFile(filePath) {
    const fileContent = readFileSync(filePath, "utf8");
    const variables = {};

    for (const line of fileContent.split(/\r?\n/u)) {
        if (!line || line.startsWith("#")) {
            continue;
        }

        const separatorIndex = line.indexOf("=");

        if (separatorIndex <= 0) {
            continue;
        }

        const key = line.slice(0, separatorIndex).trim();
        const rawValue = line.slice(separatorIndex + 1).trim();
        variables[key] = stripQuotes(rawValue);
    }

    return variables;
}

function stripQuotes(value) {
    if (
        (value.startsWith('"') && value.endsWith('"')) ||
        (value.startsWith("'") && value.endsWith("'"))
    ) {
        return value.slice(1, -1);
    }

    return value;
}

function waitForPort(host, port, timeoutMs) {
    const startedAt = Date.now();

    return new Promise((resolve, reject) => {
        const tryConnect = () => {
            const socket = net.createConnection({ host, port });

            socket.setTimeout(1000);

            socket.once("connect", () => {
                socket.destroy();
                resolve();
            });

            socket.once("timeout", () => {
                socket.destroy();
                scheduleRetry();
            });

            socket.once("error", () => {
                socket.destroy();
                scheduleRetry();
            });
        };

        const scheduleRetry = () => {
            if (Date.now() - startedAt >= timeoutMs) {
                reject(new Error("timeout"));
                return;
            }

            setTimeout(tryConnect, 1000);
        };

        tryConnect();
    });
}
