<template>
    <div class="container">
        <div v-if="isLoggedIn">

            <div class="header-bar">
                <h1>Papan Tulis Saya</h1>
                <button @click="handleLogout" class="btn-logout">
                    Logout
                </button>
            </div>

            <div v-if="selectedBoardId">
                <button @click="selectedBoardId = null" class="btn-secondary">
                    &larr; Kembali ke Daftar Board
                </button>

                <post-component :board-id="selectedBoardId"></post-component>
            </div>

            <div v-else>

                <form @submit.prevent="handleCreateBoard" class="new-board-form">
                    <div v-if="createError" class="error-message">
                        {{ createError }}
                    </div>
                    <input type="text" v-model="newBoardTitle" placeholder="Judul board baru..." required />
                    <button type="submit" :disabled="isCreatingBoard">
                        {{ isCreatingBoard ? 'Membuat...' : 'Buat Board' }}
                    </button>
                </form>
                <hr class="divider" />
                <div v-if="isLoading" class="loading">Memuat data board...</div>

                <ul v-else-if="boards.length > 0" class="board-list">
                    <li v-for="board in boards" :key="board.id" class="board-item">

                        <div classT="board-content" @click="selectBoard(board)">
                            <h3>{{ board.title }}</h3>
                            <p>{{ board.description || 'Tidak ada deskripsi' }}</p>
                        </div>

                        <button @click.stop="deleteBoard(board)" class="btn-delete">
                            &times;
                        </button>

                    </li>
                </ul>

                <div v-else-if="!isLoading && boards.length === 0">
                    <p>Anda belum memiliki board.</p>
                </div>
            </div>
        </div>

        <div v-else class="login-register-area">
            <login-component></login-component>
            <register-component></register-component>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            boards: [],
            isLoading: true,
            isLoggedIn: false,
            selectedBoardId: null,

            // Data baru untuk form
            newBoardTitle: '',
            isCreatingBoard: false,
            createError: null,
        };
    },

    mounted() {
        this.checkLoginAndFetchBoards();
    },

    methods: {
        checkLoginAndFetchBoards() {
            const token = localStorage.getItem("api_token");

            if (token) {
                axios.defaults.headers.common[
                    "Authorization"
                ] = `Bearer ${token}`;
                this.isLoggedIn = true;

                console.log("User terotentikasi. Mengambil data board...");
                this.fetchBoards();
            } else {
                console.log("User tidak login. Menampilkan pesan.");
                this.isLoading = false;
            }
        },

        async fetchBoards() {
            // (Kita pindahkan try/catch dari mounted() ke sini)
            try {
                const response = await axios.get("/api/boards");
                this.boards = response.data;
            } catch (error) {
                console.error("Gagal mengambil data board:", error);

                if (error.response && error.response.status === 401) {
                    // Token tidak valid (expired atau user dihapus)
                    localStorage.removeItem("api_token");
                    this.isLoggedIn = false;

                    // PAKSA refresh halaman untuk me-reset state semua komponen
                    window.location.reload();
                }
            } finally {
                this.isLoading = false;
            }
        },

        selectBoard(board) {
            this.selectedBoardId = board.id;
        },

        // === METHOD BARU UNTUK BUAT BOARD ===
        async handleCreateBoard() {
            if (this.newBoardTitle.trim() === '') return;

            this.isCreatingBoard = true;
            this.createError = null;

            try {
                // Panggil API 'store' Board
                const response = await axios.post('/api/boards', {
                    title: this.newBoardTitle
                });

                // Tambahkan board baru ke daftar (agar UI update)
                this.boards.unshift(response.data);

                // Bersihkan input
                this.newBoardTitle = '';

            } catch (error) {
                console.error('Gagal membuat board:', error);
                this.createError = 'Gagal membuat board. Coba lagi.';
            } finally {
                this.isCreatingBoard = false;
            }
        },

        async deleteBoard(board) {
            // Konfirmasi dulu
            if (!confirm(`Yakin ingin menghapus board "${board.title}"? Ini akan menghapus SEMUA catatannya.`)) {
                return;
            }

            try {
                // Panggil API DELETE board
                await axios.delete(`/api/boards/${board.id}`);

                // Update UI: Hapus board dari array
                this.boards = this.boards.filter(b => b.id !== board.id);

            } catch (error) {
                console.error('Gagal menghapus board:', error);
                alert('Gagal menghapus board. Coba lagi.');
            }
        },

        async handleLogout() {
            try {
                // 1. Beri tahu backend (Breeze) untuk membatalkan token
                await axios.post('/api/logout');
            } catch (error) {
                // Tidak masalah jika gagal (misal token sudah kadaluwarsa)
                console.error('Logout di server gagal, tapi tetap lanjut logout:', error);
            } finally {
                // 2. INI YANG PENTING: Hapus token dari browser
                localStorage.removeItem('api_token');

                // 3. Refresh halaman untuk me-reset state Vue
                window.location.reload();
            }
        }
    },
};
</script>

<style scoped>
.login-prompt {
    text-align: center;
    color: #666;
    padding-top: 20px;
}

.header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.header-bar h1 {
    margin: 40;
}

.btn-logout {
    padding: 8px 12px;
    background-color: #dc3545;
    /* Merah */
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.container {
    padding: 20px;
    max-width: 900px;
    margin: auto;
}

.loading {
    font-style: italic;
    color: #555;
}

.board-list {
    list-style-type: none;
    padding: 0;
}

.board-item {
    background-color: #f4f4f4;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 10px;
}

h3 {
    margin-top: 0;
}

.board-item {
    /* ... (style lama) ... */
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.board-item:hover {
    background-color: #e9e9e9;
}

.new-board-form {
    background-color: #f9f9f9;
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
}

.new-board-form input {
    flex-grow: 1;
    /* Input mengambil sisa ruang */
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.new-board-form button {
    padding: 10px 15px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.new-board-form button:disabled {
    background-color: #ccc;
}

.btn-secondary {
    /* Tombol "Kembali" */
    padding: 10px 15px;
    background-color: #6c757d;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-bottom: 20px;
}

.divider {
    border: 0;
    border-top: 1px solid #eee;
    margin: 20px 0;
}

.error-message {
    color: #721c24;
    font-size: 0.9em;
    width: 100%;
    /* Agar error di atas form */
    flex-basis: 100%;
}
.btn-delete {
  position: absolute;
  top: 5px;
  right: 5px;
  background: #ff6b6b;
  color: white;
  border: none;
  border-radius: 50%;
  width: 25px;
  height: 25px;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
  opacity: 0.3;
  transition: opacity 0.2s;
}
.board-item {
  position: relative; /* Diperlukan untuk tombol absolute */
}
.board-item:hover .btn-delete {
  opacity: 1;
}

.btn-delete-post {
  position: absolute;
  top: 3px;
  right: 3px;
  background: transparent;
  color: #aaa;
  border: none;
  font-size: 20px;
  cursor: pointer;
  opacity: 0.3;
  transition: all 0.2s;
}
.post-item {
  position: relative; /* Diperlukan untuk tombol absolute */
}
.post-item:hover .btn-delete-post {
  opacity: 1;
  color: #ff6b6b;
}
</style>