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

    <!-- Loading State -->
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
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
          <p>Nenhum filme em breve encontrado</p>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useMovieStore } from '../store/movie.js'
import { useHead } from '../composables/useHead.js'
import { useInfiniteScroll } from '../composables/useInfiniteScroll.js'
import MovieCard from '../components/MovieCard.vue'

export default {
  name: 'Upcoming',
  components: { MovieCard },
  setup() {
    const store = useMovieStore()
    const loading = ref(true)
    const isLoadingMore = ref(false)

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

    const movies = computed(() => store.movies)

    const loadMovies = async () => {
      try {
        loading.value = true
        store.resetMovies()
        await store.fetchMovies('upcoming', 1, false)
      } catch (error) {
        console.error('Erro ao carregar filmes:', error)
      } finally {
        loading.value = false
      }
    }

    const loadMore = async () => {
      if (isLoadingMore.value) return
      if (store.pagination.currentPage >= store.pagination.lastPage) {
        hasMore.value = false
        return
      }

      try {
        isLoadingMore.value = true
        await store.fetchMovies('upcoming', store.pagination.currentPage + 1, true)
      } catch (error) {
        console.error('Erro ao carregar mais filmes:', error)
      } finally {
        isLoadingMore.value = false
      }
    }

    const { loadMoreTrigger, hasMore } = useInfiniteScroll(loadMore)

    onMounted(() => {
      loadMovies()
    })

    return {
      loading,
      movies,
      loadMoreTrigger,
      isLoadingMore
    }
  }
}
</script>

<style scoped>
.upcoming-page {
  min-height: 100vh;
}
</style>