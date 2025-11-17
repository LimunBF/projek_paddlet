// 1. Muat bootstrap (dan axios) PERTAMA
import "./bootstrap";

// 2. Impor Vue dan komponen
import { createApp } from "vue";
import BoardComponent from "./components/BoardComponent.vue";
import LoginComponent from "./components/LoginComponent.vue";
import PostComponent from "./components/PostComponent.vue";
import RegisterComponent from "./components/RegisterComponent.vue";

// 3. Buat aplikasi DENGAN TEMPLATE
const app = createApp({
    // Template ini adalah "isi" dari <div id="app">
    template: `
        <div>
            <login-component></login-component>
            <register-component></register-component> 
            <board-component></board-component>
        </div>
    `,
});

// 4. Daftarkan semua komponen
app.component("board-component", BoardComponent);
app.component("login-component", LoginComponent);
app.component("post-component", PostComponent);
app.component('register-component', RegisterComponent);

// 5. Mount
app.mount("#app");

