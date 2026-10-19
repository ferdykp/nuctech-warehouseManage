import "./bootstrap";
import Alpine from "alpinejs";
import collapse from "@alpinejs/collapse";
import "@fortawesome/fontawesome-free/css/fontawesome.min.css";
import "@fortawesome/fontawesome-free/css/solid.min.css";
import "@fortawesome/fontawesome-free/css/regular.min.css";
import { initUi } from "./ui";

window.Alpine = Alpine;
Alpine.plugin(collapse);
Alpine.start();

if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", initUi);
else initUi();
