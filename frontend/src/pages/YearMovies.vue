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
import { useInfiniteScroll } from '../composables/useInfiniteScroll.js'
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
    const hasMore = ref(true)

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
          movies.value = []
        } else {
          isLoadingMore.value = true
        }

        const response = await api.get(`/movies/decade/${decade.value}`, {
          params: { 
            page: page,
            limit: 20
          }
        })

        if (page === 1) {
          movies.value = response.data.data
        } else {
          movies.value.push(...response.data.data)
        }

        hasMore.value = response.data.current_page < response.data.last_page
        currentPage.value = response.data.current_page
      } catch (error) {
        console.error('Erro ao buscar filmes por década:', error)
      } finally {
        isLoading.value = false
        isLoadingMore.value = false
      }
    }

    const loadMore = () => {
      if (!isLoadingMore.value && hasMore.value) {
        fetchMovies(currentPage.value + 1)
      }
    }

    const { loadMoreTrigger: infiniteScrollTrigger } = useInfiniteScroll(loadMore)

    onMounted(() => {
      fetchMovies()
    })

    watch(decade, () => {
      currentPage.value = 1
      hasMore.value = true
      fetchMovies()
    })

    return {
      movies,
      isLoading,
      isLoadingMore,
      loadMoreTrigger: infiniteScrollTrigger,
      decadeLabel,
      decadeRange
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
</style>
