<template>
  <div class="upcoming-page">
    <!-- Hero Section -->
    <section class="hero is-medium is-dark">
      <div class="hero-body">
        <div class="container has-text-centered">
          <p class="title is-1 has-text-white">
            <span class="icon-text">
              <span class="icon has-text-danger">
                <i class="fas fa-calendar-plus"></i>
              </span>
              <span>Próximas Estreias</span>
            </span>
          </p>
          <p class="subtitle is-4 has-text-white-ter">
            Fique por dentro dos filmes que estão chegando aos cinemas
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
          <p>Nenhum filme em breve encontrado</p>
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
import axios from 'axios'
import { useHead } from '../composables/useHead.js'
import MovieCard from '../components/MovieCard.vue'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'https://guiadefilmes.com/api'
})

export default {
  name: 'Upcoming',
  components: { MovieCard },
  setup() {
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
      title: 'Próximas Estreias - Guia de Filmes',
      meta: [
        { name: 'description', content: 'Confira os próximos lançamentos de filmes nos cinemas. Fique por dentro das estreias mais aguardadas e planeje sua próxima sessão de cinema.' },
        { property: 'og:title', content: 'Próximas Estreias - Guia de Filmes' },
        { property: 'og:description', content: 'Os filmes que estão chegando aos cinemas em breve' },
        { property: 'og:type', content: 'website' },
        { property: 'og:url', content: window.location.href },
        { name: 'twitter:card', content: 'summary_large_image' },
        { name: 'twitter:title', content: 'Próximas Estreias - Guia de Filmes' },
        { name: 'twitter:description', content: 'Fique por dentro das próximas estreias nos cinemas' }
      ],
      script: [
        {
          type: 'application/ld+json',
          innerHTML: JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'ItemList',
            name: 'Próximas Estreias de Filmes',
            description: 'Lista de filmes que estão chegando aos cinemas',
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

        const now = new Date()
        const params = {
          page: page,
          limit: 20,
          yearFrom: now.getFullYear(),
          yearTo: now.getFullYear() + 2
        }

        if (filters.value.genre) params.genre = filters.value.genre
        if (filters.value.minRating) params.minRating = filters.value.minRating

        const response = await api.get('/movies/filter', { params })

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
      fetchMovies()
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
.upcoming-page {
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