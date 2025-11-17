<template>
  <div class="container">
    <div v-if="isLoading" class="loading">
      Memuat papan tulis...
    </div>

    <div v-else-if="board">
      <h1 class="board-title">{{ board.title }}</h1>
      <p class="board-description">{{ board.description || 'Tidak ada deskripsi.' }}</p>

      <post-component :board-id="board.id"></post-component>
    </div>

    <div v-else class="error-message">
      Papan tulis tidak ditemukan.
    </div>
  </div>
</template>

<script>
export default {
  // 'props' ini akan diisi otomatis oleh Vue Router 
  // dari URL (misal: /board/3 -> id: 3)
  props: {
    id: {
      type: [String, Number], // Bisa string dari URL
      required: true
    }
  },

  data() {
    return {
      board: null,
      isLoading: true
    };
  },

  mounted() {
    this.fetchBoardDetails();
  },

  methods: {
    async fetchBoardDetails() {
      this.isLoading = true;
      try {
        // Panggil API 'show' board (yang sudah kita buat publik)
        const response = await axios.get(`/api/boards/${this.id}`);
        this.board = response.data;
      } catch (error) {
        console.error('Gagal mengambil detail board:', error);
        this.board = null; // Set null jika error (misal 404)
      } finally {
        this.isLoading = false;
      }
    }
  }
}
</script>

<style scoped>
.container {
  padding: 20px;
  max-width: 900px;
  margin: auto;
}

.board-title {
  text-align: center;
  font-size: 2.5em;
}

.board-description {
  text-align: center;
  font-size: 1.2em;
  color: #555;
  margin-bottom: 30px;
}

.loading,
.error-message {
  text-align: center;
  font-size: 1.2em;
  color: #888;
  padding: 40px;
}
</style>