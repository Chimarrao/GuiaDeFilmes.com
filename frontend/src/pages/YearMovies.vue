<template>
  <div class="year-movies">
    <section class="hero is-medium is-dark">
      <div class="hero-body">
        <div class="container has-text-centered">
          <p class="subtitle mb-2">
            <router-link to="/explorar" class="has-text-grey-light">
              <i class="fas fa-compass"></i> Explorar
            </router-link>
            <span class="mx-2">/</span>
            Década
          </p>
          <h1 class="title is-1">
            <i class="fas fa-clock-rotate-left"></i> {{ decadeLabel }}
          </h1>
          <p class="subtitle">{{ decadeRange }}</p>
        </div>
      </div>
    </section>

    <!-- Filters Section -->
    <section class="filters-section">
      <div class="container">
        <h2 class="title is-4 has-text-white mb-4">
          <span class="icon-text">
            <span class="icon has-text-danger">
              <i class="fas fa-filter"></i>
            </span>
            <span>Filtros Avançados</span>
          </span>
        </h2>
        
        <div class="columns is-multiline">
          <div class="column is-6">
            <div class="field">
              <label class="label has-text-white">Gênero</label>
              <div class="control">
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
          </div>

          <div class="column is-6">
            <div class="field">
              <label class="label has-text-white">Nota Mínima</label>
              <div class="control">
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
          </div>

          <div class="column is-12">
            <div class="buttons">
              <button class="button is-primary" @click="applyFilters">
                <span class="icon">
                  <i class="fas fa-search"></i>
                </span>
                <span>Aplicar Filtros</span>
              </button>
              <button class="button is-light" @click="clearFilters">
                <span class="icon">
                  <i class="fas fa-times"></i>
                </span>
                <span>Limpar</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Loading State -->
    <div v-if="isLoading" class="section">
      <div class="container has-text-centered">
        <div class="spinner"></div>
        <p class="mt-4">Carregando filmes...</p>
      </div>
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

          <!-- Pagination -->
          <nav v-if="totalPages > 1" class="pagination is-centered mt-6" role="navigation" aria-label="pagination">
            <button 
              class="pagination-previous" 
              :disabled="currentPage === 1"
              @click="goToPage(currentPage - 1)"
            >
              Anterior
            </button>
            <button 
              class="pagination-next" 
              :disabled="currentPage === totalPages"
              @click="goToPage(currentPage + 1)"
            >
              Próxima
            </button>
            <ul class="pagination-list">
              <li v-for="page in getPageNumbers()" :key="page">
                <span v-if="page === '...'" class="pagination-ellipsis">&hellip;</span>
                <button 
                  v-else
                  class="pagination-link" 
                  :class="{ 'is-current': page === currentPage }"
                  @click="goToPage(page)"
                >
                  {{ page }}
                </button>
              </li>
            </ul>
          </nav>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <span class="icon">
            <i class="fas fa-film"></i>
          </span>
          <p>Nenhum filme encontrado para este período</p>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useHead } from '../composables/useHead.js'
import MovieCard from '../components/MovieCard.vue'
import api from '../services/api.js'

export default {
  name: 'YearMovies',
  components: { MovieCard },
  setup() {
    const route = useRoute()
    const movies = ref([])
    const isLoading = ref(false)
    const isLoadingMore = ref(false)
    const currentPage = ref(1)
    const totalPages = ref(1)
    const filters = ref({
      genre: '',
      minRating: ''
    })

    const decade = computed(() => route.params.decade)

    const decadeLabel = computed(() => {
      const dec = parseInt(decade.value)
      if (dec < 1960) return 'Clássicos'
      return `Anos ${dec}`
    })

    const decadeRange = computed(() => {
      const dec = parseInt(decade.value)
      if (dec < 1960) return 'Filmes antes de 1960'
      return `Filmes de ${dec} a ${dec + 9}`
    })

    useHead({
      title: `${decadeLabel.value} - Explorar - Guia de Filmes`,
      description: `Descubra filmes ${decadeRange.value}`,
    })

    const fetchMovies = async (page = 1) => {
      try {
        if (page === 1) {
          isLoading.value = true
        } else {
          isLoadingMore.value = true
        }

        const dec = parseInt(decade.value)
        const params = { 
          page: page,
          limit: 20
        }

        // Adicionar filtros de ano baseado na década
        if (dec < 1960) {
          params.yearTo = 1959
        } else {
          params.yearFrom = dec
          params.yearTo = dec + 9
        }

        // Adicionar outros filtros se existirem
        if (filters.value.genre) params.genre = filters.value.genre
        if (filters.value.minRating) params.minRating = filters.value.minRating

        const response = await api.get('/movies/filter', { params })

        movies.value = response.data.data
        currentPage.value = response.data.meta?.current_page || response.data.current_page || 1
        totalPages.value = response.data.meta?.last_page || response.data.last_page || 1
      } catch (error) {
        console.error('Erro ao buscar filmes por década:', error)
      } finally {
        isLoading.value = false
        isLoadingMore.value = false
      }
    }

    const applyFilters = () => {
      currentPage.value = 1
      fetchMovies(1)
    }

    const clearFilters = () => {
      filters.value = {
        genre: '',
        minRating: ''
      }
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
        for (let i = 1; i <= total; i++) {
          pages.push(i)
        }
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

    watch(decade, () => {
      currentPage.value = 1
      movies.value = []
      filters.value = { genre: '', minRating: '' }
      fetchMovies()
    })

    return {
      movies,
      isLoading,
      isLoadingMore,
      currentPage,
      totalPages,
      decadeLabel,
      decadeRange,
      goToPage,
      getPageNumbers,
      filters,
      applyFilters,
      clearFilters
    }
  }
}
</script>

<style scoped>
.year-movies-page {
  min-height: 100vh;
}

.filters-section {
  background-color: rgba(0, 0, 0, 0.3);
  padding: 2rem 0;
  margin-bottom: 2rem;
}

.select select {
  background-color: #2b2b2b;
  color: white;
  border-color: rgba(255, 255, 255, 0.1);
}

.select select:hover {
  border-color: #dc143c;
}

.spinner {
  border: 4px solid rgba(229, 9, 20, 0.1);
  border-left-color: #e50914;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 5rem 2rem;
}

.empty-state .icon {
  font-size: 4rem;
  color: #666;
  margin-bottom: 1rem;
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
