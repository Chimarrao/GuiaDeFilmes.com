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
          <div v-for="movie in movies" :key="movie.id" class="column is-one-quarter-desktop is-one-third-tablet is-half-mobile">
            <MovieCard :movie="movie" />
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
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
import MovieCard from '../components/MovieCard.vue'

export default {
  name: 'Upcoming',
  components: { MovieCard },
  setup() {
    const store = useMovieStore()
    const loading = ref(true)

    // SEO
    useHead({
      title: 'Próximas Estreias - CineRadar',
      meta: [
        { name: 'description', content: 'Confira os próximos lançamentos de filmes nos cinemas. Fique por dentro das estreias mais aguardadas e planeje sua próxima sessão de cinema.' },
        { property: 'og:title', content: 'Próximas Estreias - CineRadar' },
        { property: 'og:description', content: 'Os filmes que estão chegando aos cinemas em breve' },
        { property: 'og:type', content: 'website' },
        { property: 'og:url', content: window.location.href },
        { name: 'twitter:card', content: 'summary_large_image' },
        { name: 'twitter:title', content: 'Próximas Estreias - CineRadar' },
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
        await store.fetchMovies('upcoming')
      } catch (error) {
        console.error('Erro ao carregar filmes:', error)
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      loadMovies()
    })

    return {
      loading,
      movies
    }
  }
}
</script>

<style scoped>
.upcoming-page {
  min-height: 100vh;
}
</style>