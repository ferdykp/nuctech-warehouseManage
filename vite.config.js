
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { readdirSync, readFileSync } from "node:fs";
import { fileURLToPath } from "node:url";

function pdfAssets() {
    return {
        name: 'local-pdf-assets',
        apply: 'build',
        generateBundle() {
            for (const directory of ['cmaps', 'standard_fonts', 'wasm', 'iccs']) {
                const source = new URL(`./node_modules/pdfjs-dist/${directory}/`, import.meta.url);
                for (const file of readdirSync(source, { withFileTypes: true })) {
                    if (file.isFile()) {
                        this.emitFile({
                            type: 'asset', fileName: `pdfjs/${directory}/${file.name}`,
                            source: readFileSync(fileURLToPath(new URL(file.name, source))),
                        });
                    }
                }
            }
        },
    };
}

export default defineConfig({
    plugins: [
        pdfAssets(),
        laravel({
            input: [
                "resources/css/app.css", "resources/js/app.js",
                "resources/js/pages/attendance.js", "resources/js/pages/schedule.js",
                "resources/js/pages/reimbursements.js",
                "resources/js/pages/salary.js", "resources/js/pages/spareparts.js", "resources/js/pages/reports.js",
                "resources/js/pages/contract-preview.js",
                "resources/js/pages/reimbursement-create.js", "resources/js/pages/reimbursement-approval.js",
            ],
            refresh: true,
        }),
    ],
});
