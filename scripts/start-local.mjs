#!/usr/bin/env node

import { copyFileSync, existsSync, readFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawn } from 'node:child_process';

const scriptDirectory = path.dirname(fileURLToPath(import.meta.url));
const projectRoot = path.resolve(scriptDirectory, '..');
const envPath = path.join(projectRoot, '.env');
const localEnvPath = path.join(projectRoot, '.env.local');
const exampleEnvPath = path.join(projectRoot, '.env.example');
const isPrintOnly = process.argv.includes('--print');

const sourcePath = resolveLocalEnvSource();
const shouldGenerateKey = sourcePath === exampleEnvPath;

if (isPrintOnly) {
    console.log(`Local env source: ${sourcePath}`);
    console.log(`Active env target: ${envPath}`);
    console.log(`Generate app key: ${shouldGenerateKey ? 'yes' : 'no'}`);
    console.log('Next command: npx concurrently "php artisan serve" "vite"');
    process.exit(0);
}

if (sourcePath !== envPath) {
    copyFileSync(sourcePath, localEnvPath);
    copyFileSync(localEnvPath, envPath);
    console.log(`Local environment restored from ${path.basename(sourcePath)}.`);
} else {
    copyFileSync(envPath, localEnvPath);
    console.log('Local environment already active. Synced .env.local.');
}

if (shouldGenerateKey || isMissingAppKey(readFileSync(envPath, 'utf8'))) {
    console.log('Generating a fresh local APP_KEY.');

    const generateKey = spawn('php', ['artisan', 'key:generate', '--force'], {
        cwd: projectRoot,
        stdio: 'inherit',
    });

    generateKey.on('exit', (generateKeyCode) => {
        if (generateKeyCode !== 0) {
            process.exit(generateKeyCode ?? 1);
        }

        copyFileSync(envPath, localEnvPath);
        runClearConfigAndStart();
    });
} else {
    runClearConfigAndStart();
}

function resolveLocalEnvSource() {
    if (existsSync(localEnvPath)) {
        return localEnvPath;
    }

    if (existsSync(envPath) && !isProdDbTunnelEnv(readFileSync(envPath, 'utf8'))) {
        return envPath;
    }

    if (existsSync(exampleEnvPath)) {
        return exampleEnvPath;
    }

    console.error('No local environment source found. Expected one of .env.local, .env, or .env.example.');
    process.exit(1);
}

function isProdDbTunnelEnv(contents) {
    return contents.includes('# PROFILE: prod-db-tunnel')
        || (contents.includes('DB_HOST=127.0.0.1')
            && contents.includes('DB_PORT=3307')
            && contents.includes('DB_DATABASE=lacaraco_lacaracolaweb'));
}

function isMissingAppKey(contents) {
    return /^APP_KEY=$/m.test(contents);
}

function runClearConfigAndStart() {
    const clearConfig = spawn('php', ['artisan', 'config:clear'], {
        cwd: projectRoot,
        stdio: 'inherit',
    });

    clearConfig.on('exit', (code) => {
        if (code !== 0) {
            process.exit(code ?? 1);
        }

        const startStack = spawn('npx', ['concurrently', 'php artisan serve', 'vite'], {
            cwd: projectRoot,
            stdio: 'inherit',
            shell: process.platform === 'win32',
        });

        startStack.on('exit', (stackCode) => {
            process.exit(stackCode ?? 0);
        });
    });
}
