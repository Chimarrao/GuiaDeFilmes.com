<template>
  <div class="filters-page">
    <section class="hero is-medium is-dark">
      <div class="hero-body">
        <div class="container has-text-centered">
          <h1 class="title is-1">
            <i class="fas fa-sliders"></i> Filtros Avançados
          </h1>
          <p class="subtitle">Encontre o filme perfeito combinando múltiplos critérios</p>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <!-- Filters Form -->
        <div class="box">
          <div class="columns is-multiline">
            <!-- Genre Filter -->
            <div class="column is-half">
              <div class="field">
                <label class="label">Gênero</label>
                <div class="control has-icons-left">
                  <div class="select is-fullwidth">
                    <select v-model="filters.genre">
                      <option value="">Todos os gêneros</option>
                      <option v-for="genre in genres" :key="genre.slug" :value="genre.slug">
                        {{ genre.name }}
                      </option>
                    </select>
                  </div>
                  <span class="icon is-left">
                    <i class="fas fa-masks-theater"></i>
                  </span>
                </div>
              </div>
            </div>

            <!-- Year Range -->
            <div class="column is-half">
              <div class="field">
                <label class="label">Ano de Lançamento</label>
                <div class="field has-addons">
                  <div class="control is-expanded has-icons-left">
                    <input v-model="filters.yearFrom" type="number" class="input" placeholder="De" min="1900" :max="currentYear">
                    <span class="icon is-left">
                      <i class="fas fa-calendar"></i>
                    </span>
                  </div>
                  <div class="control">
                    <a class="button is-static">até</a>
                  </div>
                  <div class="control is-expanded">
                    <input v-model="filters.yearTo" type="number" class="input" placeholder="Até" min="1900" :max="currentYear">
                  </div>
                </div>
              </div>
            </div>

            <!-- Rating Filter -->
            <div class="column is-half">
              <div class="field">
                <label class="label">Nota Mínima</label>
                <div class="control has-icons-left">
                  <input v-model="filters.minRating" type="number" class="input" placeholder="Ex: 7.0" min="0" max="10" step="0.1">
                  <span class="icon is-left">
                    <i class="fas fa-star"></i>
                  </span>
                </div>
              </div>
            </div>

            <!-- Country Filter -->
            <div class="column is-half">
              <div class="field">
                <label class="label">País de Origem</label>
                <div class="control has-icons-left">
                  <input v-model="filters.country" type="text" class="input" placeholder="Ex: Estados Unidos, Brasil">
                  <span class="icon is-left">
                    <i class="fas fa-globe"></i>
                  </span>
                </div>
              </div>
            </div>

            <!-- Language Filter -->
            <div class="column is-half">
              <div class="field">
                <label class="label">Idioma</label>
                <div class="control has-icons-left">
                  <div class="select is-fullwidth">
                    <select v-model="filters.language">
                      <option value="">Todos os idiomas</option>
                      <option value="en">Inglês</option>
                      <option value="pt">Português</option>
                      <option value="es">Espanhol</option>
                      <option value="fr">Francês</option>
                      <option value="it">Italiano</option>
                      <option value="de">Alemão</option>
                      <option value="ja">Japonês</option>
                      <option value="ko">Coreano</option>
                    </select>
                  </div>
                  <span class="icon is-left">
                    <i class="fas fa-language"></i>
                  </span>
                </div>
              </div>
            </div>

            <!-- Sort By -->
            <div class="column is-half">
              <div class="field">
                <label class="label">Ordenar por</label>
                <div class="control has-icons-left">
                  <div class="select is-fullwidth">
                    <select v-model="filters.sortBy">
                      <option value="popularity">Popularidade</option>
                      <option value="rating">Nota</option>
                      <option value="release_date">Data de Lançamento</option>
                      <option value="title">Título</option>
                    </select>
                  </div>
                  <span class="icon is-left">
                    <i class="fas fa-arrow-down-wide-short"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="field is-grouped is-grouped-centered mt-5">
            <div class="control">
              <button @click="applyFilters" class="button is-danger is-medium" :class="{ 'is-loading': isLoading }">
                <span class="icon">
                  <i class="fas fa-search"></i>
                </span>
                <span>Buscar Filmes</span>
              </button>
            </div>
            <div class="control">
              <button @click="clearFilters" class="button is-light is-medium">
                <span class="icon">
                  <i class="fas fa-rotate-left"></i>
                </span>
                <span>Limpar Filtros</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Results -->
        <div v-if="hasSearched" class="mt-6">
          <h2 class="title is-4">
            <i class="fas fa-film"></i> 
            Resultados 
            <span v-if="movies.length" class="tag is-danger is-medium ml-2">{{ totalResults }}</span>
          </h2>

          <div v-if="movies && movies.length" class="columns is-multiline">
            <div v-for="movie in movies" :key="movie.id" class="column is-one-quarter-desktop is-one-third-tablet is-half">
              <MovieCard :movie="movie" />
            </div>
          </div>

          <!-- Loading More -->
          <div v-if="isLoadingMore" class="has-text-centered py-5">
            <div class="spinner"></div>
          </div>

          <!-- Infinite Scroll Trigger -->
          <div ref="loadMoreTrigger" class="load-more-trigger"></div>

          <!-- Empty State -->
          <div v-if="!movies || !movies.length" class="empty-state">
            <span class="icon">
              <i class="fas fa-search"></i>
            </span>
            <p>Nenhum filme encontrado com esses critérios</p>
            <p class="has-text-grey">Tente ajustar os filtros e buscar novamente</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { useHead } from '../composables/useHead.js'
import { useInfiniteScroll } from '../composables/useInfiniteScroll.js'
import MovieCard from '../components/MovieCard.vue'
import api from '../services/api.js'

export default {
  name: 'Filters',
  components: { MovieCard },
  setup() {
    useHead({
      title: 'Filtros Avançados - Guia de Filmes',
      description: 'Use filtros avançados para encontrar o filme perfeito: gênero, ano, nota, país, idioma e mais',
    })

    const movies = ref([])
    const isLoading = ref(false)
    const isLoadingMore = ref(false)
    const hasSearched = ref(false)
    const currentPage = ref(1)
    const hasMore = ref(true)
    const totalResults = ref(0)
    const loadMoreTrigger = ref(null)

    const currentYear = new Date().getFullYear()

    const filters = ref({
      genre: '',
      yearFrom: '',
      yearTo: '',
      minRating: '',
      country: '',
      language: '',
      sortBy: 'popularity'
    })

    const genres = ref([
      { name: 'Ação', slug: 'acao' },
      { name: 'Aventura', slug: 'aventura' },
      { name: 'Comédia', slug: 'comedia' },
      { name: 'Drama', slug: 'drama' },
      { name: 'Ficção Científica', slug: 'ficcao-cientifica' },
      { name: 'Terror', slug: 'terror' },
      { name: 'Romance', slug: 'romance' },
      { name: 'Suspense', slug: 'suspense' },
      { name: 'Animação', slug: 'animacao' },
      { name: 'Crime', slug: 'crime' },
      { name: 'Documentário', slug: 'documentario' },
      { name: 'Família', slug: 'familia' },
      { name: 'Fantasia', slug: 'fantasia' },
      { name: 'Guerra', slug: 'guerra' },
      { name: 'História', slug: 'historia' },
      { name: 'Mistério', slug: 'misterio' },
      { name: 'Musical', slug: 'musical' },
      { name: 'Western', slug: 'western' },
    ])

    const fetchMovies = async (page = 1) => {
      try {
        if (page === 1) {
          isLoading.value = true
          movies.value = []
        } else {
          isLoadingMore.value = true
        }

        const params = { page, ...filters.value }
        
        // Remove empty filters
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null) {
            delete params[key]
          }
        })

        const response = await api.get('/movies/filter', { params })

        if (page === 1) {
          movies.value = response.data.data
        } else {
          movies.value.push(...response.data.data)
        }

        hasMore.value = response.data.current_page < response.data.last_page
        currentPage.value = response.data.current_page
        totalResults.value = response.data.total
        hasSearched.value = true
      } catch (error) {
        console.error('Erro ao buscar filmes com filtros:', error)
      } finally {
        isLoading.value = false
        isLoadingMore.value = false
      }
    }

    const applyFilters = () => {
      currentPage.value = 1
      hasMore.value = true
      fetchMovies()
    }

    const clearFilters = () => {
      filters.value = {
        genre: '',
        yearFrom: '',
        yearTo: '',
        minRating: '',
        country: '',
        language: '',
        sortBy: 'popularity'
      }
      movies.value = []
      hasSearched.value = false
    }

    const loadMore = () => {
      if (!isLoadingMore.value && hasMore.value) {
        fetchMovies(currentPage.value + 1)
      }
    }

    useInfiniteScroll(loadMoreTrigger, loadMore, hasMore)

    return {
      movies,
      isLoading,
      isLoadingMore,
      hasSearched,
      loadMoreTrigger,
      filters,
      genres,
      currentYear,
      totalResults,
      applyFilters,
      clearFilters
    }
  }
}
</script>

<style scoped>
.load-more-trigger {
  height: 1px;
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

.box {
  background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
  border: 1px solid rgba(229, 9, 20, 0.2);
}
</style>
