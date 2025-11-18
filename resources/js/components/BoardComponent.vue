<template>
    <div class="container">
        <div v-if="isLoggedIn">
            <div class="header-bar">
                <h1>Papan Tulis Saya</h1>
                <button @click="handleLogout" class="btn-logout">Logout</button>
            </div>

            <div
                v-if="selectedBoard"
                class="board-detail-wrapper"
                :style="{ background: selectedBoard.background || '#ffffff' }"
            >
                <div class="board-header">
                    <button @click="selectedBoard = null" class="btn-secondary">
                        &larr; Kembali
                    </button>
                    <button @click="showShareModal = true" class="btn-share">
                        🔗 Bagikan
                    </button>

                    <share-modal
                        :show="showShareModal"
                        :board-id="selectedBoard.id"
                        @close="showShareModal = false"
                    >
                    </share-modal>

                    <div class="admin-board-header">
                        <div class="board-info-card">
                            <div v-if="!isEditingInfo" class="info-view">
                                <h1 class="board-title">
                                    {{ selectedBoard.title }}
                                </h1>
                                <p class="board-desc">
                                    {{
                                        selectedBoard.description ||
                                        "Belum ada deskripsi (Klik edit untuk menambahka)n"
                                    }}
                                </p>
                                <button
                                    @click="startEditBoard"
                                    class="btn-edit-icon"
                                    title="Edit Info Board"
                                >
                                    ✏️ Edit
                                </button>
                            </div>
                            <div v-else class="info-edit">
                                <label>Judul Board</label>
                                <input
                                    v-model="editForm.title"
                                    class="input-modern title-input"
                                    placeholder="Judul Board"
                                />

                                <label>Deskripsi</label>
                                <textarea
                                    v-model="editForm.description"
                                    class="input-modern desc-input"
                                    rows="2"
                                    placeholder="Deskripsi Board"
                                ></textarea>
                                <div class="edit-actions">
                                    <button
                                        @click="saveBoardInfo"
                                        class="btn-save"
                                    >
                                        Simpan
                                    </button>
                                    <button
                                        @click="isEditingInfo = false"
                                        class="btn-cancel"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button @click="selectedBoard = null" class="btn-secondary">
                        &larr; Kembali
                    </button>

                    <div class="bg-switcher">
                        <span style="margin-right: 5px">Tema:</span>
                        <button
                            v-for="bg in availableBackgrounds"
                            :key="bg.name"
                            @click="changeBackground(bg.value)"
                            class="btn-bg-circle"
                            :style="{ background: bg.value }"
                            :title="bg.name"
                        ></button>
                    </div>

                    <div class="layout-switcher">
                        <span>Layout:</span>
                        <button @click="changeLayout('grid')">Grid</button>
                        <button @click="changeLayout('stream')">Stream</button>
                    </div>
                </div>

                <post-component
                    :board-id="selectedBoard.id"
                    :layout-type="selectedBoard.layout_type"
                    :can-delete="true"
                ></post-component>
            </div>

            <div v-else>
                <form
                    @submit.prevent="handleCreateBoard"
                    class="new-board-form"
                >
                    <div v-if="createError" class="error-message">
                        {{ createError }}
                    </div>
                    <input
                        type="text"
                        v-model="newBoardTitle"
                        placeholder="Judul board baru..."
                        required
                    />
                    <button type="submit" :disabled="isCreatingBoard">
                        {{ isCreatingBoard ? "Membuat..." : "Buat Board" }}
                    </button>
                </form>
                <hr class="divider" />
                <div v-if="isLoading" class="loading">Memuat data board...</div>

                <ul v-else-if="boards.length > 0" class="board-list">
                    <li
                        v-for="board in boards"
                        :key="board.id"
                        class="board-item"
                    >
                        <div classT="board-content" @click="selectBoard(board)">
                            <h3>{{ board.title }}</h3>
                            <p>
                                {{ board.description || "Tidak ada deskripsi" }}
                            </p>
                        </div>

                        <button
                            @click.stop="deleteBoard(board)"
                            class="btn-delete"
                        >
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
            selectedBoard: null, // menyimpan seluruh objek

            // Data baru untuk form
            newBoardTitle: "",
            isCreatingBoard: false,
            createError: null,

            isEditingInfo: false,
            editForm: { title: "", description: "" },
            showShareModal: false,

            // Pilihan Background (Warna Solid & Gradient)
            availableBackgrounds: [
                { name: "Putih", value: "#ffffff" },
                { name: "Abu-abu", value: "#f4f4f4" },
                { name: "Papan Tulis", value: "#2c3e50" }, // Gelap
                {
                    name: "Langit",
                    value: "linear-gradient(to bottom, #87CEEB, #E0F7FA)",
                },
                {
                    name: "Senja",
                    value: "linear-gradient(to right, #ff7e5f, #feb47b)",
                },
                { name: "Kayu", value: "#deb887" },
            ],
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
            console.log("Memilih board:", board.title);
            this.selectedBoard = board;
        },

        // Method simpan (mirip PublicBoard tapi Admin boleh edit Title)
        async saveBoardInfo() {
            try {
                const response = await axios.put(
                    `/api/boards/${this.selectedBoard.id}`,
                    {
                        title: this.editForm.title,
                        description: this.editForm.description,
                        // Kirim juga properti lain agar tidak hilang/reset
                        layout_type: this.selectedBoard.layout_type,
                        background: this.selectedBoard.background,
                    }
                );

                // Update UI
                this.selectedBoard.title = response.data.title;
                this.selectedBoard.description = response.data.description;
                this.isEditingInfo = false;
            } catch (error) {
                console.error("Gagal update:", error);
                alert("Gagal update info.");
            }
        },

        // Method untuk masuk mode edit (panggil saat klik tombol edit)
        startEditBoard() {
            this.editForm.title = this.selectedBoard.title;
            this.editForm.description = this.selectedBoard.description;
            this.isEditingInfo = true;
        },

        // === METHOD BARU UNTUK BUAT BOARD ===
        async handleCreateBoard() {
            if (this.newBoardTitle.trim() === "") return;

            this.isCreatingBoard = true;
            this.createError = null;

            try {
                // Panggil API 'store' Board
                const response = await axios.post("/api/boards", {
                    title: this.newBoardTitle,
                });

                // Tambahkan board baru ke daftar (agar UI update)
                this.boards.unshift(response.data);

                // Bersihkan input
                this.newBoardTitle = "";
            } catch (error) {
                console.error("Gagal membuat board:", error);
                this.createError = "Gagal membuat board. Coba lagi.";
            } finally {
                this.isCreatingBoard = false;
            }
        },

        async deleteBoard(board) {
            // Konfirmasi dulu
            if (
                !confirm(
                    `Yakin ingin menghapus board "${board.title}"? Ini akan menghapus SEMUA catatannya.`
                )
            ) {
                return;
            }

            try {
                // Panggil API DELETE board
                await axios.delete(`/api/boards/${board.id}`);

                // Update UI: Hapus board dari array
                this.boards = this.boards.filter((b) => b.id !== board.id);
            } catch (error) {
                console.error("Gagal menghapus board:", error);
                alert("Gagal menghapus board. Coba lagi.");
            }
        },

        async changeLayout(newLayout) {
            if (this.selectedBoard.layout_type === newLayout) return; // Jangan diubah jika sama

            try {
                // Panggil API untuk simpan
                const response = await axios.put(
                    `/api/boards/${this.selectedBoard.id}`,
                    {
                        layout_type: newLayout,
                    }
                );

                // Update state lokal (ini penting!)
                this.selectedBoard.layout_type = response.data.layout_type;
                console.log("Layout diubah ke:", newLayout);
            } catch (error) {
                console.error("Gagal mengubah layout:", error);
                alert("Gagal mengubah layout.");
            }
        },

        async handleLogout() {
            try {
                // 1. Beri tahu backend (Breeze) untuk membatalkan token
                await axios.post("/api/logout");
            } catch (error) {
                // Tidak masalah jika gagal (misal token sudah kadaluwarsa)
                console.error(
                    "Logout di server gagal, tapi tetap lanjut logout:",
                    error
                );
            } finally {
                // 2. INI YANG PENTING: Hapus token dari browser
                localStorage.removeItem("api_token");

                // 3. Refresh halaman untuk me-reset state Vue
                window.location.reload();
            }
        },

        async changeBackground(bgValue) {
            // Update UI langsung biar cepat
            this.selectedBoard.background = bgValue;

            try {
                // Simpan ke database
                await axios.put(`/api/boards/${this.selectedBoard.id}`, {
                    background: bgValue,
                });
                console.log("Background tersimpan!");
            } catch (error) {
                console.error("Gagal ganti background:", error);
            }
        },
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
    position: relative;
    /* Diperlukan untuk tombol absolute */
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
    position: relative;
    /* Diperlukan untuk tombol absolute */
}

.post-item:hover .btn-delete-post {
    opacity: 1;

    color: #ff6b6b;
}

.board-detail-wrapper {
    padding: 20px;
    min-height: 80vh;
    /* Tinggi minimal */
    border-radius: 8px;
    transition: background 0.5s ease;
    /* Animasi transisi halus */
}

/* Tombol Bulat Warna */
.bg-switcher {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-right: 15px;
}

.btn-bg-circle {
    width: 25px;
    height: 25px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    cursor: pointer;
    transition: transform 0.2s;
}

.btn-bg-circle:hover {
    transform: scale(1.2);
}

.admin-board-header {
    display: flex;
    justify-content: center;
    margin-bottom: 30px;
    position: relative;
    z-index: 10;
    /* Agar di atas elemen lain */
}

/* Kartu Putih Cantik */
.board-info-card {
    background-color: rgba(255, 255, 255, 0.5);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.4);

    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 600px;
    text-align: center;
    transition: all 0.3s ease;
}
/* Tipografi Judul */
.board-title {
    margin: 0 0 10px 0;
    font-size: 2.2rem;
    color: #2c3e50;
    font-weight: 700;
    letter-spacing: -0.5px;
}

/* Tipografi Deskripsi */
.board-desc {
    color: #555;
    font-size: 1.1rem;
    line-height: 1.5;
    margin-bottom: 15px;
}

/* Tombol Edit Kecil */
.btn-edit-icon {
    background: #f0f0f0;
    border: none;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    color: #666;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-edit-icon:hover {
    background: #e0e0e0;
    color: #333;
}

/* --- Gaya Mode Edit --- */
.info-edit {
    text-align: left;
    /* Form rata kiri biar rapi */
}

.info-edit label {
    display: block;
    font-size: 0.85rem;
    font-weight: bold;
    color: #888;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.input-modern {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 2px solid #eee;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s;
    box-sizing: border-box;
}

.input-modern:focus {
    outline: none;
    border-color: #007bff;
}

.title-input {
    font-weight: bold;
    font-size: 1.2rem;
}

.edit-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Tombol Simpan/Batal yang lebih keren */
.btn-save {
    background: #007bff;
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
}

.btn-cancel {
    background: transparent;
    color: #666;
    border: 1px solid #ccc;
    padding: 8px 20px;
    border-radius: 6px;
    cursor: pointer;
}

.btn-save:hover {
    background: #0056b3;
}

.btn-cancel:hover {
    background: #f9f9f9;
}

.btn-share {
    margin: 0 10px;
    padding: 8px 15px;
    background-color: #6f42c1; /* Warna Ungu */
    color: white;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    font-weight: bold;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}
.btn-share:hover {
    background-color: #5a32a3;
}
</style>
