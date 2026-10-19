
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
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
