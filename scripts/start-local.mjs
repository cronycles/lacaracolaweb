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

bootstrapLocalEnvProfile();
const shouldGenerateKey = isMissingAppKey(readFileSync(localEnvPath, 'utf8'));

if (isPrintOnly) {
    console.log(`Local env source: ${localEnvPath}`);
    console.log(`Active env target: ${envPath}`);
    console.log(`Generate app key: ${shouldGenerateKey ? 'yes' : 'no'}`);
    console.log('Next command: npx concurrently --kill-others-on-fail --names APP,VITE --prefix-colors green,cyan "php artisan serve" "npm run dev -- --host 127.0.0.1 --port 5173"');
    process.exit(0);
}

copyFileSync(localEnvPath, envPath);
console.log('Local environment loaded from .env.local into .env.');

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

function bootstrapLocalEnvProfile() {
    if (existsSync(localEnvPath)) {
        return;
    }

    if (!existsSync(exampleEnvPath)) {
        console.error('Missing .env.local and .env.example. Cannot bootstrap local profile.');
        process.exit(1);
    }

    copyFileSync(exampleEnvPath, localEnvPath);
    console.log('Created .env.local from .env.example.');
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

        const startStack = spawn('npx', [
            'concurrently',
            '--kill-others-on-fail',
            '--names',
            'APP,VITE',
            '--prefix-colors',
            'green,cyan',
            'php artisan serve',
            'npm run dev -- --host 127.0.0.1 --port 5173',
        ], {
            cwd: projectRoot,
            stdio: 'inherit',
            shell: process.platform === 'win32',
        });

        startStack.on('exit', (stackCode) => {
            process.exit(stackCode ?? 0);
        });
    });
}
