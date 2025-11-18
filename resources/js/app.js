// 1. Muat bootstrap (dan axios) PERTAMA
import "./bootstrap";

// 2. Impor Vue dan komponen
import { createApp } from "vue";
import router from './router.js'; // <-- IMPOR ROUTER KITA
import BoardComponent from "./components/BoardComponent.vue";
import LoginComponent from "./components/LoginComponent.vue";
import PostComponent from "./components/PostComponent.vue";
import RegisterComponent from "./components/RegisterComponent.vue";
import ShareModal from './components/ShareModal.vue';

const app = createApp({});

// Daftarkan semua komponen secara global
app.component('board-component', BoardComponent);
app.component('login-component', LoginComponent);
app.component('post-component', PostComponent);
app.component('register-component', RegisterComponent);
app.component('share-modal', ShareModal);

// GUNAKAN ROUTER
app.use(router);

//  Mount
app.mount('#app');