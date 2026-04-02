#!/usr/bin/env node

import { execFileSync } from 'node:child_process';
import { copyFileSync, existsSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDirectory = path.dirname(fileURLToPath(import.meta.url));
const projectRoot = path.resolve(scriptDirectory, '..');
const envFile = path.join(projectRoot, '.env.prod-local');
const localEnvFile = path.join(projectRoot, '.env.local');
const exampleEnvFile = path.join(projectRoot, '.env.example');
const isPrintOnly = process.argv.includes('--print');

if (process.platform !== 'darwin') {
    console.error('start:dbprod is available only on macOS because it uses Terminal.app via osascript.');
    process.exit(1);
}

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
    console.log('Created .env.local from .env.example.');
}

const tunnelCommand = [
    'echo "Opening SSH tunnel on 127.0.0.1:3307 -> remote MySQL localhost:3306"',
    'echo "Leave this terminal open. If asked, enter your SSH password."',
    'ssh -o ExitOnForwardFailure=yes -i ~/.ssh/id_rsa_supporthost -p 2299 -L 3307:localhost:3306 lacaraco@65.108.143.244 -N',
].join(' && ');

const appCommands = [
    `cd ${shellQuote(projectRoot)}`,
    'echo "Waiting for SSH tunnel on 127.0.0.1:3307..."',
    'while ! nc -z 127.0.0.1 3307 >/dev/null 2>&1; do sleep 1; done',
    'echo "Tunnel is ready. Starting Laravel against production DB."',
    'cp .env.prod-local .env',
    'php artisan config:clear',
    'php artisan serve',
].join(' && ');

if (isPrintOnly) {
    console.log('Tunnel command:');
    console.log(tunnelCommand);
    console.log('');
    console.log('App command:');
    console.log(appCommands);
    process.exit(0);
}

const appleScript = [
    'tell application "Terminal"',
    'activate',
    `do script ${toAppleScriptString(tunnelCommand)}`,
    'delay 0.5',
    `do script ${toAppleScriptString(appCommands)}`,
    'end tell',
].join('\n');

execFileSync('osascript', ['-e', appleScript], { stdio: 'inherit' });

function shellQuote(value) {
    return `'${value.replace(/'/g, `'"'"'`)}'`;
}

function toAppleScriptString(value) {
    return `"${value.replaceAll('\\', '\\\\').replaceAll('"', '\\"')}"`;
}
