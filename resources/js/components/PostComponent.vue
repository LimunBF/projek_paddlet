<template>
    <div class="post-container">
        <h2>Catatan di Papan Tulis</h2>

        <form @submit.prevent="handleNewPost" class="new-post-form">
            <div v-if="postError" class="error-message">
                {{ postError }}
            </div>
            <textarea v-model="newPostContent" placeholder="Tulis catatan baru..." rows="3" required></textarea>
            <button type="submit" :disabled="isSubmitting">
                {{ isSubmitting ? 'Menyimpan...' : 'Tambah Catatan' }}
            </button>
        </form>

        <hr class="divider" />

        <div v-if="isLoading">Memuat catatan...</div>

        <draggable v-else-if="posts.length > 0" v-model="posts" @end="onDragEnd" item-key="id" class="post-list-grid">
            <template #item="{ element: post }">
                <div class="post-item">
                    <p>{{ post.content }}</p>
                    <small>oleh: {{ post.user ? post.user.name : 'Tamu' }}</small>
                </div>
            </template>
        </draggable>

        <div v-else>
            <p>Belum ada catatan di papan tulis ini.</p>
        </div>
    </div>
</template>

<script>
// 3. IMPORT YANG SUDAH DIPERBAIKI (DENGAN {})
import draggable from 'vuedraggable';

export default {
    components: {
        draggable,
    },

    props: {
        boardId: {
            type: Number,
            required: true,
        },
    },

    data() {
        return {
            posts: [],
            isLoading: true,
            newPostContent: '',
            isSubmitting: false,
            postError: null,
        };
    },

    mounted() {
        this.fetchPosts();
    },

    methods: {
        async fetchPosts() {
            if (!this.boardId) return;
            this.isLoading = true;
            try {
                const response = await axios.get(
                    `/api/boards/${this.boardId}/posts`
                );
                this.posts = response.data.sort((a, b) => {
                    if (a.position_y !== b.position_y) {
                        return a.position_y - b.position_y;
                    }
                    return a.position_x - b.position_x;
                });
            } catch (error) {
                console.error("Gagal mengambil posts:", error);
            } finally {
                this.isLoading = false;
            }
        },

        async handleNewPost() {
            if (this.newPostContent.trim() === '') return;
            this.isSubmitting = true;
            this.postError = null;
            try {
                const response = await axios.post(
                    `/api/boards/${this.boardId}/posts`,
                    { content: this.newPostContent }
                );

                // Cek jika balasan API tidak menyertakan 'user'
                if (!response.data.user) {
                    // Kita set user 'null' karena ini bisa jadi postingan tamu
                    response.data.user = null;
                }
                this.posts.unshift(response.data);
                this.newPostContent = '';
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

                // Panggil API (hanya jika user-nya login DAN pemilik post)
                // Kita perlu tambahkan cek otorisasi di frontend
                // Untuk sekarang, kita panggil saja, tapi API akan menolaknya jika bukan pemilik
                axios.put(`/api/posts/${post.id}`, newPosition)
                    .then(response => {
                        console.log(`Posisi ${post.id} disimpan!`);
                    })
                    .catch(error => {
                        // Error 403 (Forbidden) jika tamu mencoba menggeser itu normal
                        if (error.response.status !== 403) {
                            console.error(`Gagal simpan posisi ${post.id}:`, error);
                        }
                    });
            });
        }
    },
};
</script>

<style scoped>
/* ... (SEMUA STYLE ANDA SUDAH BENAR) ... */
.post-container {
    margin-top: 20px;
}

.post-list-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.new-post-form {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.new-post-form textarea {
    width: 100%;
    padding: 10px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-bottom: 10px;
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
    background-color: #fffbe5;
    border: 1px solid #eee;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    cursor: grab;
}

.post-item:active {
    cursor: grabbing;
}

.post-item p {
    margin-top: 0;
}

.post-item small {
    color: #555;
}
</style>