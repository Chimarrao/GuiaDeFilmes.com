<template>
  <div class="search-page">
    <!-- Page Header -->
    <section class="hero is-dark">
      <div class="hero-body">
        <div class="container">
          <h1 class="title is-1 has-text-white">
            <span class="icon-text">
              <span class="icon has-text-danger">
                <i class="fas fa-search"></i>
              </span>
              <span>Buscar Filmes</span>
            </span>
          </h1>
          <p class="subtitle has-text-white-ter">Encontre seus filmes favoritos</p>
          
          <!-- Search Input -->
          <div class="field mt-5">
            <p class="control has-icons-left is-large">
              <input 
                class="input is-large" 
                type="search" 
                placeholder="Digite o nome do filme..." 
                v-model="searchQuery"
                @keyup.enter="performSearch"
                autofocus
              >
              <span class="icon is-left">
                <i class="fas fa-search"></i>
              </span>
            </p>
          </div>
          
          <button class="button is-primary is-large mt-3" @click="performSearch">
            <span class="icon">
              <i class="fas fa-search"></i>
            </span>
            <span>Buscar</span>
          </button>
        </div>
      </div>
    </section>

    <!-- Search Results -->
    <section class="section">
      <div class="container">
        <!-- Loading State -->
        <div v-if="loading" class="loading">
          <div class="spinner"></div>
        </div>

        <!-- Results -->
        <div v-else-if="results.length > 0">
          <h2 class="title is-3 has-text-white mb-5">
            Resultados para "{{ lastSearchQuery }}" ({{ results.length }} encontrados)
          </h2>
          
          <div class="columns is-multiline">
            <div v-for="movie in results" :key="movie.id" class="column is-one-fifth-desktop is-one-quarter-tablet is-half-mobile">
              <MovieCard :movie="movie" />
            </div>
          </div>
        </div>

        <!-- No Results -->
        <div v-else-if="lastSearchQuery" class="empty-state">
          <span class="icon">
            <i class="fas fa-search"></i>
          </span>
          <p>Nenhum filme encontrado para "{{ lastSearchQuery }}"</p>
          <p class="has-text-white-ter">Tente buscar com outros termos</p>
        </div>

        <!-- Initial State -->
        <div v-else class="empty-state">
          <span class="icon">
            <i class="fas fa-film"></i>
          </span>
          <p>Digite o nome de um filme para buscar</p>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useMovieStore } from '../store/movie.js'
import { useHead } from '../composables/useHead.js'
import MovieCard from '../components/MovieCard.vue'

export default {
  name: 'Search',
  components: {
    MovieCard
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const store = useMovieStore()
    const loading = ref(false)
    const searchQuery = ref('')
    const lastSearchQuery = ref('')
    const results = ref([])

    useHead({
      title: 'Buscar Filmes - CineRadar',
      meta: [
        { name: 'description', content: 'Busque por seus filmes favoritos no CineRadar' }
      ]
    })

    const performSearch = async () => {
      const query = searchQuery.value.trim()
      if (!query) return

      try {
        loading.value = true
        lastSearchQuery.value = query
        
        // Atualiza URL com query parameter
        router.push({ query: { q: query } })
        
        // Busca filmes
        await store.fetchMovies()
        
        // Filtra resultados pelo título
        const allMovies = store.movies
        results.value = allMovies.filter(movie => 
          movie.title.toLowerCase().includes(query.toLowerCase())
        )
      } catch (error) {
        console.error('Erro ao buscar filmes:', error)
        results.value = []
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      // Se veio com query parameter na URL
      const q = route.query.q
      if (q) {
        searchQuery.value = q
        performSearch()
      }
    })

    return {
      loading,
      searchQuery,
      lastSearchQuery,
      results,
      performSearch
    }
  }
}
</script>

<style scoped>
.search-page {
  min-height: calc(100vh - 200px);
}
</style>
