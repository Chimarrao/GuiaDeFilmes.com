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
import { useRoute, useRouter } from 'vue-router'
import { useHead } from '../composables/useHead.js'
import MovieCard from '../components/MovieCard.vue'
import api from '../services/api.js'

export default {
  name: 'YearMovies',
  components: { MovieCard },
  setup() {
    const route = useRoute()
    const router = useRouter()
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

    // Mapeamento de slugs para décadas
    const decadeMap = {
      '2020s': { start: 2020, end: 2029, label: 'Anos 2020' },
      '2010s': { start: 2010, end: 2019, label: 'Anos 2010' },
      '2000s': { start: 2000, end: 2009, label: 'Anos 2000' },
      '1990s': { start: 1990, end: 1999, label: 'Anos 1990' },
      '1980s': { start: 1980, end: 1989, label: 'Anos 1980' },
      '1970s': { start: 1970, end: 1979, label: 'Anos 1970' },
      '1960s': { start: 1960, end: 1969, label: 'Anos 1960' },
      '1950s': { start: 1950, end: 1959, label: 'Anos 1950' },
      '1940s': { start: 1940, end: 1949, label: 'Anos 1940' },
      '1930s': { start: 1930, end: 1939, label: 'Anos 1930' },
      '1920s': { start: 1920, end: 1929, label: 'Anos 1920' },
      'pre-1920': { start: 1900, end: 1919, label: 'Pré-1920' }
    }

    /**
     * Retorna informações sobre a década baseado no slug da URL
     * @returns {Object} Objeto com start, end e label da década
     */
    const getDecadeInfo = () => {
      const slug = decade.value
      
      // Se o slug está no mapa, retorna
      if (decadeMap[slug]) {
        return decadeMap[slug]
      }
      
      // Fallback: tenta converter para número (compatibilidade)
      const dec = parseInt(slug)
      if (dec >= 1900) {
        return {
          start: dec,
          end: dec + 9,
          label: `Anos ${dec}`
        }
      }
      
      // Padrão
      return { start: 2020, end: 2029, label: 'Anos 2020' }
    }

    const decadeLabel = computed(() => getDecadeInfo().label)

    const decadeRange = computed(() => {
      const info = getDecadeInfo()
      return `Filmes de ${info.start} a ${info.end}`
    })

    // Atualizar title quando a década mudar
    watch(decadeLabel, (newLabel) => {
      if (newLabel) {
        useHead({
          title: `${newLabel} - Explorar - Guia de Filmes`,
          meta: [
            { name: 'description', content: `Descubra filmes ${decadeRange.value}` }
          ]
        })
      }
    }, { immediate: true })

    /**
     * Busca filmes de uma década específica com paginação
     * @param {number} page - Número da página a ser carregada
     */
    const fetchMovies = async (page = 1) => {
      try {
        if (page === 1) {
          isLoading.value = true
        } else {
          isLoadingMore.value = true
        }

        const params = { 
          page: page,
          limit: 20
        }

        // Adicionar filtros se existirem
        if (filters.value.genre) params.genre = filters.value.genre
        if (filters.value.minRating) params.minRating = filters.value.minRating

        // Usar endpoint específico de década
        const response = await api.get(`/movies/decade/${decade.value}`, { params })

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

    /**
     * Aplica os filtros selecionados e recarrega a primeira página
     */
    const applyFilters = () => {
      currentPage.value = 1
      fetchMovies(1)
    }

    /**
     * Limpa todos os filtros e recarrega os filmes
     */
    const clearFilters = () => {
      filters.value = {
        genre: '',
        minRating: ''
      }
      currentPage.value = 1
      fetchMovies(1)
    }

    /**
     * Navega para uma página específica
     * @param {number} page - Número da página de destino
     */
    const goToPage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        router.push({ query: { ...route.query, page } })
        window.scrollTo({ top: 0, behavior: 'smooth' })
        fetchMovies(page)
      }
    }

    /**
     * Gera array de números de página para paginação com reticências
     * @returns {Array} Array com números de página e '...' para omissões
     */
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
      const pageFromUrl = parseInt(route.query.page) || 1
      currentPage.value = pageFromUrl
      fetchMovies(pageFromUrl)
    })

    watch(decade, () => {
      currentPage.value = 1
      movies.value = []
      filters.value = { genre: '', minRating: '' }
      router.push({ query: {} })
      fetchMovies(1)
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
/* Container principal da página */
.year-movies-page {
  min-height: 100vh;
}

/* === Seção de Filtros === */

/* Container dos filtros com fundo escuro semi-transparente */
.filters-section {
  background-color: rgba(0, 0, 0, 0.3);
  padding: 2rem 0;
  margin-bottom: 2rem;
}

/* Estilo dos selects de filtro */
.select select {
  background-color: #2b2b2b;
  color: white;
  border-color: rgba(255, 255, 255, 0.1);
}

/* Borda vermelha ao passar mouse nos selects */
.select select:hover {
  border-color: #dc143c;
}

/* === Loading Spinner === */

/* Spinner animado com cores da marca */
.spinner {
  border: 4px solid rgba(229, 9, 20, 0.1);
  border-left-color: #e50914;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto;
}

/* Animação de rotação do spinner */
@keyframes spin {
  to { transform: rotate(360deg); }
}

/* === Estado Vazio === */

/* Container do estado sem resultados */
.empty-state {
  text-align: center;
  padding: 5rem 2rem;
}

/* Ícone grande do estado vazio */
.empty-state .icon {
  font-size: 4rem;
  color: #666;
  margin-bottom: 1rem;
}

/* === Estilos de Paginação === */

/* Container da paginação */
.pagination {
  margin-top: 3rem;
  margin-bottom: 2rem;
}

/* Botões de navegação e links de página */
.pagination-previous,
.pagination-next,
.pagination-link {
  background-color: #2b2b2b;
  border-color: #404040;
  color: #e0e0e0;
  transition: all 0.3s ease;
}

/* Efeito hover nos botões ativos */
.pagination-previous:hover:not(:disabled),
.pagination-next:hover:not(:disabled),
.pagination-link:hover:not(.is-current) {
  background-color: #dc143c;
  border-color: #dc143c;
  color: #fff;
  transform: translateY(-2px);
}

/* Página atual destacada */
.pagination-link.is-current {
  background-color: #dc143c;
  border-color: #dc143c;
  color: #fff;
  font-weight: 600;
}

/* Botões desabilitados */
.pagination-previous:disabled,
.pagination-next:disabled {
  background-color: #1a1a1a;
  border-color: #2b2b2b;
  color: #555;
  cursor: not-allowed;
  opacity: 0.5;
}

/* Reticências entre números de página */
.pagination-ellipsis {
  color: #888;
}
</style>
