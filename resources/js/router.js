import { createRouter, createWebHistory } from 'vue-router';

import BoardComponent from './components/BoardComponent.vue'; 
import PublicBoard from './components/PublicBoard.vue';

const routes = [
    {
        // "Mode 1": Dasbor pemilik (harus login)
        path: '/',
        name: 'dashboard',
        component: BoardComponent 
    },
    {
        // "Mode 2": Halaman tamu (publik)
        path: '/board/:id', // :id adalah parameter
        name: 'publicBoard',
        component: PublicBoard, // <-- GUNAKAN KOMPONEN BARU
        props: true // Ini akan oper ':id' dari URL sebagai prop
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes, 
});

export default router;