<template>
    <div class="container-fluid">
        <div v-if="isLoading" class="loading">Memuat papan tulis...</div>

        <div
            v-else-if="board"
            class="public-board-wrapper"
            :style="{ background: board.background || '#ffffff' }"
        >
            <div class="board-header-content">
                <div class="header-actions">
                    <button
                        @click="isEditingInfo = true"
                        class="btn-edit-info"
                    ></button>

                    <button
                        @click="showShareModal = true"
                        class="btn-share-public"
                    >
                        🔗 Bagikan
                    </button>
                </div>
                <share-modal
                    :show="showShareModal"
                    :board-id="board.id"
                    @close="showShareModal = false"
                ></share-modal>

                <div v-if="!isEditingInfo">
                    <h1 class="board-title">{{ board.title }}</h1>
                    <p class="board-description">
                        {{ board.description || "Tidak ada deskripsi." }}
                    </p>

                    <button @click="isEditingInfo = true" class="btn-edit-info">
                        ✏️ Edit Info
                    </button>
                </div>

                <div v-else class="edit-info-form">
                    <h2 class="readonly-title">{{ editForm.title }}</h2>
                    <small style="color: #666"
                        >(Hanya Admin yang bisa mengubah judul)</small
                    >

                    <textarea
                        v-model="editForm.description"
                        class="input-desc"
                        rows="3"
                        placeholder="Deskripsi Board"
                    ></textarea>

                    <div class="edit-actions">
                        <button @click="saveBoardInfo" class="btn-save">
                            Simpan Deskripsi
                        </button>
                        <button @click="cancelEdit" class="btn-cancel">
                            Batal
                        </button>
                    </div>
                </div>
            </div>

            <post-component
                :board-id="board.id"
                :layout-type="board.layout_type"
                :can-delete="false"
            ></post-component>
        </div>

        <div v-else class="error-message">Papan tulis tidak ditemukan.</div>
    </div>
</template>

<script>
export default {
    props: {
        id: {
            type: [String, Number],
            required: true,
        },
    },

    data() {
        return {
            board: null,
            isLoading: true,
            isEditingInfo: false,
            showShareModal: false,
            editForm: {
                title: "",
                description: "",
            },
        };
    },

    mounted() {
        this.fetchBoardDetails();
    },

    methods: {
        async fetchBoardDetails() {
            this.isLoading = true;
            try {
                const response = await axios.get(`/api/boards/${this.id}`);
                this.board = response.data;

                // Isi form edit dengan data awal
                this.editForm.title = this.board.title;
                this.editForm.description = this.board.description;
            } catch (error) {
                console.error("Gagal mengambil detail board:", error);
                this.board = null;
            } finally {
                this.isLoading = false;
            }
        },

        // === PINDAHKAN FUNGSI KE DALAM SINI ===

        async saveBoardInfo() {
            try {
                const response = await axios.put(`/api/boards/${this.id}`, {
                    title: this.editForm.title,
                    description: this.editForm.description,
                });

                // Update tampilan dengan data baru dari server
                this.board.title = response.data.title;
                this.board.description = response.data.description;

                // Keluar dari mode edit
                this.isEditingInfo = false;
            } catch (error) {
                console.error("Gagal update board:", error);
                alert("Gagal menyimpan perubahan.");
            }
        },

        cancelEdit() {
            // Reset form ke data asli dan tutup mode edit
            this.editForm.title = this.board.title;
            this.editForm.description = this.board.description;
            this.isEditingInfo = false;
        },
    },
};
</script>

<style scoped>
/* Reset container padding agar background full */
.container-fluid {
    padding: 0;
    margin: 0;
}

.public-board-wrapper {
    padding: 40px 20px;
    min-height: 100vh;
    /* Agar background memenuhi setidaknya satu layar penuh */
    transition: background 0.5s ease;
}

.board-header-content {
    text-align: center;
    margin-bottom: 40px;

    /* === EFEK GLASSMORPHISM (KACA) === */
    background-color: rgba(255, 255, 255, 0.4);
    /* Putih transparan (40%) */
    backdrop-filter: blur(12px);
    /* Efek blur di belakang kaca */
    -webkit-backdrop-filter: blur(12px);
    /* Safari support */
    border: 1px solid rgba(255, 255, 255, 0.3);
    /* Border tipis transparan */
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
    /* Bayangan halus */

    padding: 30px;
    border-radius: 20px;
    /* Lebih bulat */
    display: inline-block;
    position: relative;
    left: 50%;
    transform: translateX(-50%);
    max-width: 80%;
}

.board-title {
    font-size: 2.8em;
    margin: 0 0 10px 0;
    color: #222;
    text-shadow: 0 1px 1px rgba(255, 255, 255, 0.5);
    /* Agar teks terbaca jelas */
}

.board-description {
    font-size: 1.2em;
    color: #444;
    margin: 0;
}

.loading,
.error-message {
    text-align: center;
    font-size: 1.2em;
    color: #888;
    padding: 40px;
}

/* Tombol Edit Kecil */
.btn-edit-info {
    background: transparent;
    border: 1px solid #666;
    color: #333;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    cursor: pointer;
    opacity: 0.6;
    transition: opacity 0.2s;
    margin-top: 10px;
}

.btn-edit-info:hover {
    opacity: 1;
    background: rgba(255, 255, 255, 0.5);
}

/* Form Edit */
.edit-info-form {
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 300px;
}

.input-title {
    font-size: 1.5em;
    padding: 5px;
    text-align: center;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.input-desc {
    font-size: 1em;
    padding: 5px;
    text-align: center;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.edit-actions {
    display: flex;
    justify-content: center;
    gap: 10px;
}

.btn-save {
    background-color: #28a745;
    color: white;
    border: none;
    padding: 5px 15px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-cancel {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 5px 15px;
    border-radius: 4px;
    cursor: pointer;
}
.header-actions {
    margin-top: 15px;
    display: flex;
    justify-content: center;
    gap: 10px;
}
.btn-share-public {
    background: rgba(255, 255, 255, 0.8);
    border: none;
    padding: 5px 12px;
    border-radius: 20px;
    cursor: pointer;
    color: #333;
    font-weight: bold;
}
.btn-share-public:hover {
    background: white;
}
</style>
