// @ts-check
import js from "@eslint/js";
import tsPlugin from "@typescript-eslint/eslint-plugin";
import tsParser from "@typescript-eslint/parser";
import importPlugin from "eslint-plugin-import";

export default [
    js.configs.recommended,
    {
        files: ["resources/ts/**/*.ts"],
        languageOptions: {
            parser: tsParser,
            parserOptions: {
                project: "./tsconfig.json",
                sourceType: "module",
            },
            globals: {
                AbortController: "readonly",
                CustomEvent: "readonly",
                Element: "readonly",
                FormData: "readonly",
                HTMLInputElement: "readonly",
                MutationObserver: "readonly",
                URLSearchParams: "readonly",
                clearInterval: "readonly",
                clearTimeout: "readonly",
                document: "readonly",
                fetch: "readonly",
                navigator: "readonly",
                requestAnimationFrame: "readonly",
                setInterval: "readonly",
                setTimeout: "readonly",
                window: "readonly",
            },
        },
        plugins: {
            "@typescript-eslint": tsPlugin,
            import: importPlugin,
        },
        rules: {
            // TypeScript strict rules
            "@typescript-eslint/no-explicit-any": "warn",
            "@typescript-eslint/no-unused-vars": [
                "error",
                { argsIgnorePattern: "^_" },
            ],
            "@typescript-eslint/explicit-function-return-type": "warn",

            // Import order
            "import/order": ["warn", { alphabetize: { order: "asc" } }],

            // General
            "no-console": ["warn", { allow: ["warn", "error"] }],
        },
    },
    {
        ignores: ["node_modules/**", "vendor/**", "public/build/**"],
    },
];
