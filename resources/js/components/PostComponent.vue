<template>
    <div class="post-container">
        <h2>Catatan di Papan Tulis</h2>

        <div v-if="newPostAvailable" class="notification-bar" @click="loadNewPosts">
            Ada catatan baru! Klik di sini untuk memuat.
        </div>

        <form @submit.prevent="handleNewPost" class="new-post-form">
            <div v-if="postError" class="error-message">
                {{ postError }}
            </div>

            <div v-if="isGuest" style="margin-bottom: 10px;">
                <input type="text" v-model="guestName" placeholder="Nama Anda (Wajib)" class="input-guest-name"
                    required>
            </div>

            <textarea v-model="newPostContent" placeholder="Tulis catatan (opsional jika ada gambar)..."
                rows="3"></textarea>

            <div v-if="previewUrl" class="image-preview">
                <img :src="previewUrl" alt="Preview Gambar">
                <button type="button" @click="cancelImage" class="btn-cancel-img">&times;</button>
            </div>

            <div class="form-actions">
                <label class="btn-upload">
                    📷 Tambah Gambar
                    <input type="file" @change="onFileChange" accept="image/*" style="display: none;">
                </label>

                <button type="submit" :disabled="isSubmitting || isLoading">
                    {{ isSubmitting ? 'Menyimpan...' : 'Tambah Catatan' }}
                </button>
            </div>
        </form>
        <hr class="divider" />
        <div v-if="isLoading">Memuat catatan...</div>

        <draggable v-else-if="posts.length > 0" v-model="posts" @end="onDragEnd" item-key="id" :class="layoutClass">
            <template #item="{ element: post }">
                <div class="post-item" :style="{ backgroundColor: post.color || '#ffffff' }">
                    <button v-if="canDelete" @click="deletePost(post)" class="btn-delete-post">&times;</button>

                    <img v-if="post.image_path" :src="post.image_path" alt="Gambar Post" class="post-image"
                        @click="openLightbox(post.image_path, post.caption)">

                    <p v-if="post.caption" class="post-caption">{{ post.caption }}</p>
                    
                    <p v-if="post.content">{{ post.content }}</p>

                    <small>oleh: {{ post.user ? post.user.name : (post.guest_name || 'Tamu') }}</small>
                </div>
            </template>
        </draggable>
        
        <div v-else>
            <p>Belum ada catatan di papan tulis ini.</p>
        </div>

        <div v-if="showLightbox" class="lightbox-overlay" @click="closeLightbox">
            <div class="lightbox-content" @click.stop>
                <button class="close-lightbox" @click="closeLightbox">&times;</button>
                <img :src="lightboxImageUrl" alt="Gambar Diperbesar">
                <p v-if="lightboxImageCaption" class="lightbox-caption">{{ lightboxImageCaption }}</p>
            </div>
        </div>

    </div>
</template>

<script>
import draggable from 'vuedraggable';

export default {
    components: {
        draggable,
    },

    props: {
        boardId: {
            type: [String, Number],
            required: true,
        },
        layoutType: {
            type: String,
            default: 'grid'
        },
        canDelete: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            posts: [],
            isLoading: true,
            newPostContent: '',
            newPostCaption: '', // Pastikan ini ada untuk caption gambar
            isSubmitting: false,
            postError: null,

            // === DATA BARU UNTUK POLLING ===
            timer: null,
            knownPostCount: 0,
            newPostAvailable: false,

            // === DATA BARU UNTUK GAMBAR ===
            selectedFile: null,
            previewUrl: null,

            // === DATA BARU UNTUK LIGHTBOX ===
            showLightbox: false,
            lightboxImageUrl: '',
            lightboxImageCaption: '',

            colors: [
                '#ffadad', '#ffd6a5', '#fdffb6', '#caffbf', '#9bf6ff', 
                '#a0c4ff', '#bdb2ff', '#ffc6ff', '#ffffff',
            ],

            guestName: '',
            isGuest: !localStorage.getItem('api_token'),
        };
    },

    mounted() {
        this.fetchPosts();
        this.startPolling();
    },

    computed: {
        layoutClass() {
            if (this.layoutType === 'stream') {
                return 'post-list-stream';
            }
            return 'post-list-grid';
        }
    },

    unmounted() {
        this.stopPolling();
    },

    methods: {
        async fetchPosts() {
            if (!this.boardId) return;
            this.isLoading = true;
            this.newPostAvailable = false;
            try {
                const response = await axios.get(
                    `/api/boards/${this.boardId}/posts`
                );
                this.posts = response.data.sort((a, b) => {
                    if (a.position_y !== b.position_y) return a.position_y - b.position_y;
                    return a.position_x - b.position_x; // Sortir fallback
                });

                this.knownPostCount = this.posts.length;

            } catch (error) {
                console.error("Gagal mengambil posts:", error);
            } finally {
                this.isLoading = false;
            }
        },

        loadNewPosts() {
            this.fetchPosts();
            this.startPolling();
        },

        async handleNewPost() {
            if (this.isGuest && this.guestName.trim() === '') {
                this.postError = 'Nama wajib diisi!';
                return;
            }

            if (this.newPostContent.trim() === '' && !this.selectedFile) {
                this.postError = 'Tulis catatan atau pilih gambar.';
                return;
            }

            this.isSubmitting = true;
            this.postError = null;
            let uploadedImageUrl = null;

            const randomColor = this.colors[Math.floor(Math.random() * this.colors.length)];

            if (this.selectedFile) {
                uploadedImageUrl = await this.uploadImage();
                if (!uploadedImageUrl) {
                    this.isSubmitting = false;
                    return;
                }
            }

            try {
                const response = await axios.post(
                    `/api/boards/${this.boardId}/posts`,
                    {
                        content: this.newPostContent,
                        image_path: uploadedImageUrl,
                        color: randomColor,
                        caption: this.newPostCaption, // Kirim caption inputan user
                        guest_name: this.guestName
                    }
                );

                if (!response.data.user) {
                    response.data.user = null;
                }
                this.posts.unshift(response.data);

                this.newPostContent = '';
                this.newPostCaption = ''; // Reset caption
                this.cancelImage();
                this.knownPostCount = this.posts.length;

            } catch (error) {
                console.error('Gagal menyimpan post:', error);
                this.postError = 'Gagal menyimpan catatan. Coba lagi.';
            } finally {
                this.isSubmitting = false;
            }
        },

        async onDragEnd(event) {
            console.log('Drag selesai!', event);
            this.posts.forEach((post, index) => {
                const newPosition = {
                    position_y: index,
                    position_x: 0
                };
                post.position_y = newPosition.position_y;

                axios.put(`/api/posts/${post.id}`, newPosition)
                    .then(response => { console.log(`Posisi ${post.id} disimpan!`); })
                    .catch(error => { console.error(`Gagal simpan posisi:`, error); });
            });
        },

        async deletePost(post) {
            if (!confirm('Yakin ingin menghapus catatan ini?')) return;
            try {
                await axios.delete(`/api/posts/${post.id}`);
                this.posts = this.posts.filter(p => p.id !== post.id);
                this.knownPostCount = this.posts.length;
            } catch (error) {
                alert('Gagal menghapus post. Anda mungkin bukan pemiliknya.');
            }
        },

        startPolling() {
            this.stopPolling();
            this.timer = setInterval(() => { this.checkStatus(); }, 10000);
        },

        stopPolling() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },

        async checkStatus() {
            try {
                const response = await axios.get(`/api/boards/${this.boardId}/status`);
                const serverPostCount = response.data.total_posts;
                if (serverPostCount > this.knownPostCount) {
                    this.newPostAvailable = true;
                    this.stopPolling();
                }
            } catch (error) { console.error('Gagal polling status:', error); }
        },

        onFileChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.selectedFile = file;
            this.previewUrl = URL.createObjectURL(file);
        },

        cancelImage() {
            this.selectedFile = null;
            this.previewUrl = null;
        },

        async uploadImage() {
            if (!this.selectedFile) return null;
            const formData = new FormData();
            formData.append('image', this.selectedFile);
            try {
                const response = await axios.post('/api/images/upload', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                return response.data.url;
            } catch (error) {
                if (error.response && error.response.status === 401) {
                    this.postError = 'Anda harus login untuk mengupload gambar.';
                } else {
                    this.postError = 'Gagal mengupload gambar. (Maks 2MB)';
                }
                return null;
            }
        },

        openLightbox(imageUrl, caption) {
            this.lightboxImageUrl = imageUrl;
            this.lightboxImageCaption = caption;
            this.showLightbox = true;
        },
        closeLightbox() {
            this.showLightbox = false;
            this.lightboxImageUrl = '';
            this.lightboxImageCaption = '';
        },
    },
};
</script>

<style scoped>
.post-container {
    margin-top: 20px;
}

.post-list-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.post-list-stream {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
}

.post-list-stream .post-item {
    width: 100%;
    max-width: 600px;
    cursor: grab;
}

.new-post-form {
    background-color: rgba(255, 255, 255, 0.5);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.4);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.new-post-form textarea {
    width: 100%;
    padding: 12px;
    box-sizing: border-box;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    margin-bottom: 10px;
    background-color: rgba(255, 255, 255, 0.6);
    transition: background-color 0.3s, box-shadow 0.3s;
}

.new-post-form button {
    padding: 10px 15px;
    background-color: #28a745;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.new-post-form button:disabled {
    background-color: #ccc;
}

.new-post-form textarea:focus {
    outline: none;
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.3);
}

.divider {
    border: 0;
    border-top: 1px solid #eee;
    margin: 20px 0;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 15px;
}

.post-item {
    background-color: #fcfcfc;
    border-radius: 12px;
    padding: 15px;
    margin: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    position: relative;
    transition: all 0.2s ease-in-out;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    cursor: grab;
}

.post-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    cursor: grab;
}

.post-item:hover .btn-delete-post {
    opacity: 1;
    color: #ff6b6b;
}

.post-item.dragging {
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
    transform: scale(1.02);
    z-index: 1000;
}

.post-image {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin-bottom: 10px;
    cursor: zoom-in;
    display: block;
}

.post-item p {
    margin: 0 0 8px 0;
    color: #333;
    line-height: 1.4;
    word-break: break-word;
}
.post-caption {
    font-weight: bold;
    font-size: 0.95em;
    color: #222;
    margin-bottom: 8px;
}

.post-item small {
    font-size: 0.8em;
    color: #666;
    text-align: right;
    display: block;
    margin-top: auto;
}

.lightbox-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.lightbox-content {
    position: relative;
    background-color: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    max-width: 90%;
    max-height: 90%;
    overflow: auto;
    text-align: center;
}

.lightbox-content img {
    max-width: 100%;
    height: auto;
    display: block;
    margin: 0 auto;
    border-radius: 8px;
}

.lightbox-caption {
    margin-top: 15px;
    font-size: 1.1em;
    color: #333;
    font-weight: bold;
}

.close-lightbox {
    position: absolute;
    top: 10px;
    right: 15px;
    background: none;
    border: none;
    font-size: 2em;
    color: #666;
    cursor: pointer;
    z-index: 10000;
}

.close-lightbox:hover {
    color: #333;
}

.notification-bar {
    background-color: #007bff;
    color: white;
    padding: 10px 15px;
    border-radius: 4px;
    text-align: center;
    cursor: pointer;
    margin-bottom: 15px;
    font-weight: bold;
    transition: background-color 0.2s;
}

.notification-bar:hover {
    background-color: #0056b3;
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

.form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
}

.btn-upload {
    padding: 10px 15px;
    background-color: #6c757d;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.image-preview {
    position: relative;
    margin-top: 10px;
}

.image-preview img {
    width: 100%;
    max-height: 200px;
    object-fit: cover;
    border-radius: 4px;
}

.btn-cancel-img {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    border-radius: 50%;
    width: 25px;
    height: 25px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
}

.input-guest-name {
    width: 100%;
    padding: 10px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    background-color: rgba(255, 255, 255, 0.6);
    margin-bottom: 10px;
}
</style>