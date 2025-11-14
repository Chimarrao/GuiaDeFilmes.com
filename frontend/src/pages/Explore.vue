<template>
  <div class="explore-page">
    <section class="hero is-medium is-dark">
      <div class="hero-body">
        <div class="container has-text-centered">
          <h1 class="title is-1">
            <i class="fas fa-compass"></i> Explorar
          </h1>
          <p class="subtitle">Descubra filmes por gênero, década ou use filtros avançados</p>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <!-- Advanced Filters Section -->
        <div class="mb-6">
          <h2 class="title is-3 mb-4 has-text-white">
            <i class="fas fa-filter"></i> Busca Avançada
          </h2>
          <router-link to="/buscar">
            <Button class="p-button-danger p-button-lg w-full">
              <i class="fas fa-sliders mr-2"></i>
              <span>Buscar com Filtros Avançados</span>
            </Button>
          </router-link>
        </div>

        <!-- Genres Section -->
        <div class="mb-6">
          <h2 class="title is-3 mb-4 has-text-white">
            <i class="fas fa-masks-theater"></i> Gêneros
          </h2>
          <div class="columns is-multiline is-mobile">
            <div v-for="genre in genres" :key="genre.id" class="column is-one-quarter-desktop is-one-third-tablet is-half-mobile">
              <div class="genre-card" @click="$router.push(`/explorar/genero/${genre.slug}`)">
                <Card class="explore-card">
                  <template #content>
                    <div class="card-content has-text-centered">
                      <span class="icon is-large has-text-danger mb-2">
                        <i :class="`fas ${genre.icon} fa-3x`"></i>
                      </span>
                      <h3 class="title is-5 has-text-white">{{ genre.name }}</h3>
                    </div>
                  </template>
                </Card>
              </div>
            </div>
          </div>
        </div>

        <!-- Decades Section -->
        <div class="mb-6">
          <h2 class="title is-3 mb-4 has-text-white">
            <i class="fas fa-calendar-alt"></i> Décadas
          </h2>
          <div class="columns is-multiline is-mobile">
            <div v-for="decade in decades" :key="decade.id" class="column is-one-quarter-desktop is-one-third-tablet is-half-mobile">
              <div class="decade-card" @click="$router.push(`/explorar/decada/${decade.slug}`)">
                <Card class="explore-card">
                  <template #content>
                    <div class="card-content has-text-centered">
                      <span class="icon is-large has-text-danger mb-2">
                        <i :class="`fas ${decade.icon} fa-3x`"></i>
                      </span>
                      <h3 class="title is-5 has-text-white">{{ decade.name }}</h3>
                    </div>
                  </template>
                </Card>
              </div>
            </div>
          </div>
        </div>
        <div class="mb-6">
          <h2 class="title is-3 mb-4 has-text-white">
            <i class="fas fa-globe"></i> Nacionalidades
          </h2>
          <div class="columns is-multiline is-mobile">
            <div v-for="country in displayedCountries" :key="country.code" class="column is-one-quarter-desktop is-one-third-tablet is-half-mobile">
              <router-link :to="`/explorar/pais/${country.code}`" class="country-card">
                <div class="card">
                  <div class="card-content has-text-centered">
                    <img :src="country.flag" :alt="`Bandeira ${country.name}`" class="country-flag mb-2" />
                    <h3 class="title is-5 has-text-white">{{ country.name }}</h3>
                    <p class="subtitle is-6 has-text-grey-light" v-if="country.count">{{ country.count }} filmes</p>
                  </div>
                </div>
              </router-link>
            </div>
          </div>
          
          <!-- Botão para mostrar mais países -->
          <div v-if="hiddenCountriesCount > 0 && !showAllCountries" class="has-text-centered mt-4">
            <button @click="showAllCountries = true" class="button is-danger is-outlined">
              <span class="icon">
                <i class="fas fa-plus"></i>
              </span>
              <span>Mostrar mais {{ hiddenCountriesCount }} países</span>
            </button>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useHead } from '../composables/useHead.js'
import axios from 'axios'
import Card from 'primevue/card'
import Button from 'primevue/button'

export default {
  name: 'Explore',
  components: {
    Card,
    Button
  },
  setup() {
    useHead({
      title: 'Explorar - Guia de Filmes',
      description: 'Descubra filmes por gênero, década ou use filtros avançados para encontrar o filme perfeito',
    })

    const genres = ref([
      { id: 1, name: 'Ação', slug: 'acao', icon: 'fa-explosion' },
      { id: 2, name: 'Aventura', slug: 'aventura', icon: 'fa-compass' },
      { id: 3, name: 'Comédia', slug: 'comedia', icon: 'fa-face-laugh' },
      { id: 4, name: 'Drama', slug: 'drama', icon: 'fa-masks-theater' },
      { id: 5, name: 'Ficção Científica', slug: 'ficcao-cientifica', icon: 'fa-rocket' },
      { id: 6, name: 'Terror', slug: 'terror', icon: 'fa-ghost' },
      { id: 7, name: 'Romance', slug: 'romance', icon: 'fa-heart' },
      { id: 8, name: 'Suspense', slug: 'suspense', icon: 'fa-magnifying-glass' },
      { id: 9, name: 'Animação', slug: 'animacao', icon: 'fa-wand-magic-sparkles' },
      { id: 10, name: 'Crime', slug: 'crime', icon: 'fa-gun' },
      { id: 11, name: 'Documentário', slug: 'documentario', icon: 'fa-video' },
      { id: 12, name: 'Família', slug: 'familia', icon: 'fa-children' },
      { id: 13, name: 'Fantasia', slug: 'fantasia', icon: 'fa-dragon' },
      { id: 14, name: 'Guerra', slug: 'guerra', icon: 'fa-shield' },
      { id: 15, name: 'História', slug: 'historia', icon: 'fa-landmark' },
      { id: 16, name: 'Mistério', slug: 'misterio', icon: 'fa-mask' },
      { id: 17, name: 'Musical', slug: 'musical', icon: 'fa-music' },
      { id: 18, name: 'Western', slug: 'western', icon: 'fa-hat-cowboy' },
    ])

    const decades = ref([
      { id: 1, name: '2020s', slug: '2020s', icon: 'fa-calendar-days' },
      { id: 2, name: '2010s', slug: '2010s', icon: 'fa-calendar-days' },
      { id: 3, name: '2000s', slug: '2000s', icon: 'fa-calendar-days' },
      { id: 4, name: '1990s', slug: '1990s', icon: 'fa-calendar-days' },
      { id: 5, name: '1980s', slug: '1980s', icon: 'fa-calendar-days' },
      { id: 6, name: '1970s', slug: '1970s', icon: 'fa-calendar-days' },
      { id: 7, name: '1960s', slug: '1960s', icon: 'fa-calendar-days' },
      { id: 8, name: '1950s', slug: '1950s', icon: 'fa-calendar-days' },
      { id: 9, name: '1940s', slug: '1940s', icon: 'fa-calendar-days' },
      { id: 10, name: '1930s', slug: '1930s', icon: 'fa-calendar-days' },
      { id: 11, name: '1920s', slug: '1920s', icon: 'fa-calendar-days' },
      { id: 12, name: 'Pré-1920', slug: 'pre-1920', icon: 'fa-calendar-days' },
    ])

    const countries = ref([])
    const showAllCountries = ref(false)

    /**
     * Carrega países do backend com contagem de filmes
     */
    const loadCountries = async () => {
      try {
        const response = await axios.get('/api/countries')
        countries.value = response.data
      } catch (error) {
        console.error('Erro ao carregar países:', error)
      }
    }

    /**
     * Retorna países a exibir com base no filtro
     */
    const displayedCountries = computed(() => {
      if (showAllCountries.value) {
        return countries.value
      }
      // Exibe países com 100+ filmes
      return countries.value.filter(c => c.count >= 100)
    })

    /**
     * Conta quantos países estão ocultos
     */
    const hiddenCountriesCount = computed(() => {
      return countries.value.length - displayedCountries.value.length
    })

    onMounted(() => {
      loadCountries()
    })

    return {
      genres,
      decades,
      countries,
      displayedCountries,
      showAllCountries,
      hiddenCountriesCount
    }
  }
}
</script>

<style scoped>
/* Cards de gênero, década e país - comportamento base */
.genre-card,
.decade-card,
.country-card {
  display: block;
  transition: transform 0.2s, box-shadow 0.2s;
  cursor: pointer;
}

/* Efeito hover nos cards - elevação e sombra vermelha */
.genre-card:hover,
.decade-card:hover,
.country-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(229, 9, 20, 0.3);
}

/* Estilo interno dos cards - gradiente escuro e borda */
.genre-card .card,
.decade-card .card,
.country-card .card {
  background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
  border: 1px solid rgba(229, 9, 20, 0.2);
  height: 100%;
  min-height: 180px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Destaque da borda ao passar o mouse */
.genre-card:hover .card,
.decade-card:hover .card,
.country-card:hover .card {
  border-color: #e50914;
}

/* Bandeiras dos países nos cards */
.country-flag {
  width: 40px;
  height: 30px;
  border-radius: 4px;
  object-fit: cover;
}

/* Conteúdo interno dos cards */
.card-content {
  padding: 2rem 1rem;
}
</style>
