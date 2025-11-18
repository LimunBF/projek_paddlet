<template>
    <div v-if="show" class="modal-overlay" @click="$emit('close')">
        <div class="modal-content" @click.stop>

            <div class="modal-header">
                <h3>Bagikan Papan Tulis</h3>
                <button class="btn-close" @click="$emit('close')">&times;</button>
            </div>

            <div class="modal-body">
                <p>Undang orang lain untuk berkolaborasi di papan ini.</p>

                <div class="copy-link-wrapper">
                    <input type="text" :value="shareUrl" readonly ref="linkInput">
                    <button @click="copyLink" :class="{ 'copied': isCopied }">
                        {{ isCopied ? 'Tersalin!' : 'Salin' }}
                    </button>
                </div>

                <hr class="divider">

                <div class="qr-wrapper">
                    <p>Atau scan QR Code ini:</p>
                    <qrcode-vue :value="shareUrl" :size="200" level="H" />
                </div>
            </div>

        </div>
    </div>
</template>

<script>
import QrcodeVue from 'qrcode.vue'

export default {
    components: {
        QrcodeVue,
    },
    props: {
        show: Boolean,
        boardId: [Number, String]
    },
    data() {
        return {
            isCopied: false
        }
    },
    computed: {
        shareUrl() {
            // Gabungkan URL dasar website + /board/ + ID
            return `${window.location.origin}/board/${this.boardId}`;
        }
    },
    methods: {
        copyLink() {
            // Fitur copy bawaan browser modern
            navigator.clipboard.writeText(this.shareUrl).then(() => {
                this.isCopied = true;
                setTimeout(() => { this.isCopied = false }, 2000); // Reset teks setelah 2 detik
            });
        }
    }
}
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.modal-content {
    background: white;
    padding: 25px;
    border-radius: 15px;
    width: 90%;
    max-width: 400px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    text-align: center;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.modal-header h3 {
    margin: 0;
    color: #333;
}

.btn-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #999;
}

.btn-close:hover {
    color: #333;
}

/* Input Copy Link */
.copy-link-wrapper {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.copy-link-wrapper input {
    flex-grow: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
    color: #555;
}

.copy-link-wrapper button {
    padding: 0 20px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.3s;
}

.copy-link-wrapper button.copied {
    background: #28a745;
    /* Hijau kalau berhasil */
}

.divider {
    margin: 20px 0;
    border: 0;
    border-top: 1px solid #eee;
}

.qr-wrapper p {
    margin-bottom: 15px;
    color: #666;
}
</style>