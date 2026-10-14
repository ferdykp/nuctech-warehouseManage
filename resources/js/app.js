import "./bootstrap";
import Alpine from "alpinejs";
import { initUi } from "./ui";

window.Alpine = Alpine;
Alpine.start();

if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", initUi);
else initUi();
