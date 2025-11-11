<template>
  <div class="in-theaters-page">
    <!-- Hero Section -->
    <section class="hero is-medium is-primary">
      <div class="hero-body">
        <div class="container has-text-centered">
          <p class="title is-1 has-text-white">
            <span class="icon-text">
              <span class="icon">
                <i class="fas fa-ticket-alt"></i>
              </span>
              <span>Em Cartaz</span>
            </span>
          </p>
          <p class="subtitle is-4 has-text-white-ter">
            Filmes disponíveis nos cinemas agora
          </p>
        </div>
      </div>
    </section>

    <!-- Filters Section -->
    <section class="filters-section">
      <div class="container">
        <h2 class="title is-5 has-text-white mb-4">
          <span class="icon-text">
            <span class="icon has-text-danger"><i class="fas fa-filter"></i></span>
            <span>Filtros</span>
          </span>
        </h2>
        <div class="columns">
          <div class="column is-5">
            <div class="field">
              <label class="label has-text-white">Gênero</label>
              <div class="select is-fullwidth">
                <select v-model="filters.genre">
                  <option value="">Todos</option>
                  <option value="acao">Ação</option>
                  <option value="aventura">Aventura</option>
                  <option value="comedia">Comédia</option>
                  <option value="drama">Drama</option>
                  <option value="terror">Terror</option>
                  <option value="ficcao-cientifica">Ficção Científica</option>
                  <option value="romance">Romance</option>
                  <option value="suspense">Suspense</option>
                  <option value="animacao">Animação</option>
                </select>
              </div>
            </div>
          </div>
          <div class="column is-5">
            <div class="field">
              <label class="label has-text-white">Nota Mínima</label>
              <div class="select is-fullwidth">
                <select v-model="filters.minRating">
                  <option value="">Todas</option>
                  <option value="7">7+</option>
                  <option value="8">8+</option>
                  <option value="9">9+</option>
                </select>
              </div>
            </div>
          </div>
          <div class="column is-2">
            <label class="label">&nbsp;</label>
            <button class="button is-primary is-fullwidth" @click="applyFilters">
              <span class="icon"><i class="fas fa-search"></i></span>
              <span>Filtrar</span>
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Loading State -->
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
    </div>

    <!-- Movies Grid -->
    <section v-else class="section">
      <div class="container">
        <div v-if="movies && movies.length">
          <div class="columns is-multiline is-mobile">
            <div v-for="movie in movies" :key="movie.id" class="column is-one-quarter-desktop is-one-third-tablet is-half-mobile">
              <MovieCard :movie="movie" />
            </div>
          </div>

          <!-- Loading More -->
          <div v-if="isLoadingMore" class="has-text-centered py-5">
            <div class="spinner"></div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <span class="icon">
            <i class="fas fa-film"></i>
          </span>
          <p>Nenhum filme em cartaz no momento</p>
        </div>

        <!-- Pagination (outside conditional blocks) -->
        <nav v-if="totalPages > 1" class="pagination is-centered mt-6" role="navigation" aria-label="pagination">
          <button class="pagination-previous" :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">Anterior</button>
          <button class="pagination-next" :disabled="currentPage === totalPages" @click="goToPage(currentPage + 1)">Próxima</button>
          <ul class="pagination-list">
            <li v-for="page in getPageNumbers()" :key="page">
              <span v-if="page === '...'" class="pagination-ellipsis">&hellip;</span>
              <button v-else class="pagination-link" :class="{ 'is-current': page === currentPage }" @click="goToPage(page)">{{ page }}</button>
            </li>
          </ul>
        </nav>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useHead } from '../composables/useHead.js'
import MovieCard from '../components/MovieCard.vue'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'https://guiadefilmes.com/api'
})

export default {
  name: 'InTheaters',
  components: { MovieCard },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const movies = ref([])
    const loading = ref(true)
    const isLoadingMore = ref(false)
    const currentPage = ref(1)
    const totalPages = ref(1)
    const filters = ref({
      genre: '',
      minRating: ''
    })

    // SEO
    useHead({
      title: 'Filmes em Cartaz - Guia de Filmes',
      meta: [
        { name: 'description', content: 'Descubra quais filmes estão em cartaz nos cinemas. Confira a lista completa de filmes disponíveis para assistir agora mesmo.' },
        { property: 'og:title', content: 'Filmes em Cartaz - Guia de Filmes' },
        { property: 'og:description', content: 'Filmes disponíveis nos cinemas agora' },
        { property: 'og:type', content: 'website' },
        { property: 'og:url', content: window.location.href },
        { name: 'twitter:card', content: 'summary_large_image' },
        { name: 'twitter:title', content: 'Filmes em Cartaz - Guia de Filmes' },
        { name: 'twitter:description', content: 'Veja os filmes que estão passando nos cinemas' }
      ],
      script: [
        {
          type: 'application/ld+json',
          innerHTML: JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'ItemList',
            name: 'Filmes em Cartaz nos Cinemas',
            description: 'Lista de filmes atualmente disponíveis nos cinemas',
            url: window.location.href
          })
        }
      ]
    })

    const fetchMovies = async (page = 1) => {
      try {
        if (page === 1) {
          loading.value = true
        } else {
          isLoadingMore.value = true
        }

        const params = {
          page: page,
          limit: 20
        }

        if (filters.value.genre) params.genre = filters.value.genre
        if (filters.value.minRating) params.minRating = filters.value.minRating

        const response = await api.get('/movies/in-theaters', { params })

        movies.value = response.data.data
        currentPage.value = response.data.meta?.current_page || response.data.current_page || 1
        totalPages.value = response.data.meta?.last_page || response.data.last_page || 1
      } catch (error) {
        console.error('Erro ao carregar filmes:', error)
      } finally {
        loading.value = false
        isLoadingMore.value = false
      }
    }

    const applyFilters = () => {
      currentPage.value = 1
      fetchMovies(1)
    }

    const goToPage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        router.push({ query: { ...route.query, page } })
        window.scrollTo({ top: 0, behavior: 'smooth' })
        fetchMovies(page)
      }
    }

    const getPageNumbers = () => {
      const pages = []
      const current = currentPage.value
      const total = totalPages.value
      if (total <= 7) {
        for (let i = 1; i <= total; i++) pages.push(i)
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) pages.push(i)
          pages.push('...')
          pages.push(total)
        } else if (current >= total - 3) {
          pages.push(1)
          pages.push('...')
          for (let i = total - 4; i <= total; i++) pages.push(i)
        } else {
          pages.push(1)
          pages.push('...')
          for (let i = current - 1; i <= current + 1; i++) pages.push(i)
          pages.push('...')
          pages.push(total)
        }
      }
      return pages
    }

    onMounted(() => {
      const pageFromUrl = parseInt(route.query.page) || 1
      currentPage.value = pageFromUrl
      fetchMovies(pageFromUrl)
    })

    return {
      loading,
      movies,
      isLoadingMore,
      currentPage,
      totalPages,
      goToPage,
      getPageNumbers,
      filters,
      applyFilters
    }
  }
}
</script>

<style scoped>
.in-theaters-page {
  min-height: 100vh;
}

.filters-section {
  background-color: rgba(0, 0, 0, 0.3);
  padding: 1.5rem 0;
  margin-bottom: 1rem;
}

.select select {
  background-color: #2b2b2b;
  color: white;
  border-color: rgba(255, 255, 255, 0.1);
}

.select select:hover {
  border-color: #dc143c;
}

/* Pagination Styles */
.pagination {
  margin-top: 3rem;
  margin-bottom: 2rem;
}

.pagination-previous,
.pagination-next,
.pagination-link {
  background-color: #2b2b2b;
  border-color: #404040;
  color: #e0e0e0;
  transition: all 0.3s ease;
}

.pagination-previous:hover:not(:disabled),
.pagination-next:hover:not(:disabled),
.pagination-link:hover:not(.is-current) {
  background-color: #dc143c;
  border-color: #dc143c;
  color: #fff;
  transform: translateY(-2px);
}

.pagination-link.is-current {
  background-color: #dc143c;
  border-color: #dc143c;
  color: #fff;
  font-weight: 600;
}

.pagination-previous:disabled,
.pagination-next:disabled {
  background-color: #1a1a1a;
  border-color: #2b2b2b;
  color: #555;
  cursor: not-allowed;
  opacity: 0.5;
}

.pagination-ellipsis {
  color: #888;
}
</style>