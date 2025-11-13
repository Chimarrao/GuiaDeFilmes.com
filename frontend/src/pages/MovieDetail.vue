<template>
  <div class="movie-detail">
    <!-- Loading State -->
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
    </div>

    <!-- Movie Content -->
    <div v-else-if="movie">
      <!-- Hero Header with Backdrop -->
      <div class="movie-detail-header" :style="{ backgroundImage: movie ? getBackdropUrl() : 'url(https://via.placeholder.com/1920x1080/1a1a1a/e50914?text=GUIA+DE+FILMES)' }">
        <div class="movie-detail-overlay"></div>
        <div class="container movie-detail-content">
          <div class="columns">
            <div class="column is-one-third">
              <figure class="image">
                <img 
                  class="movie-poster-large" 
                  :src="movie ? getPosterUrl() : 'https://via.placeholder.com/500x750/1a1a1a/e50914?text=SEM+POSTER'" 
                  :alt="movie ? movie.title : 'Carregando...'"
                />
              </figure>
            </div>

            <div class="column movie-info">
              <h1 class="title is-1 has-text-white">{{ movie.title }}</h1>
              
              <div class="movie-meta mb-4">
                <div class="movie-meta-item">
                  <span class="icon">
                    <i class="fas fa-calendar-alt"></i>
                  </span>
                  <span>{{ formatDate(movie.release_date) }}</span>
                </div>

                <div class="movie-meta-item" v-if="movie.runtime">
                  <span class="icon">
                    <i class="fas fa-clock"></i>
                  </span>
                  <span>{{ movie.runtime }} min</span>
                </div>

                <div class="movie-meta-item" v-if="movie.status">
                  <span class="tag is-primary is-medium">{{ formatStatus(movie.status) }}</span>
                </div>
              </div>

              <!-- Rating TMDB -->
              <div class="rating-section mb-4" v-if="movie.tmdb_rating">
                <RatingBadge :score="movie.tmdb_rating" class="mr-3" style="width: 60px; height: 60px;" />
                <span class="has-text-white is-size-4">
                  <strong class="rating-score">{{ movie.tmdb_rating }}</strong>/10
                </span>
                <span class="has-text-white-ter ml-2">({{ formatNumber(movie.tmdb_vote_count) }} votos)</span>
              </div>

              <div class="genres mb-4" v-if="movie.genres && movie.genres.length">
                <router-link 
                  v-for="genre in movie.genres" 
                  :key="genre" 
                  :to="getGenreLink(genre)"
                  class="tag is-medium is-dark"
                  style="text-decoration: none;"
                >
                  {{ genre }}
                </router-link>
              </div>

              <div v-if="movie.tagline" class="mb-4">
                <p class="has-text-white-ter is-size-5 is-italic">"{{ movie.tagline }}"</p>
              </div>

              <div class="content has-text-white-ter">
                <h2 class="title is-4 has-text-white">Sinopse</h2>
                <p class="is-size-5" style="text-align: justify;">{{ movie ? getSynopsis() : 'Carregando sinopse...' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="container">
        <div class="section">
          
          <!-- SEO: Release Date Block -->
          <div class="box seo-box mb-5" style="background-color: var(--background-card);">
            <h2 class="title is-3 has-text-white mb-4">
              <span class="icon-text">
                <span class="icon has-text-danger">
                  <i class="fas fa-calendar-check"></i>
                </span>
                <span>{{ movie ? getReleaseBlockTitle() : 'Carregando...' }}</span>
              </span>
            </h2>
            <div class="content has-text-white-ter is-size-5">
              <p>
                <strong class="has-text-danger">{{ movie.title }}</strong> {{ movie ? getReleaseDateText() : '' }}
                <strong class="has-text-white">{{ formatDate(movie.release_date) }}</strong>{{ getYearContext() }}.
              </p>
              <p>{{ movie ? getReleaseContext() : '' }}</p>
              <p>{{ movie ? getAvailabilityText() : '' }}</p>
            </div>
          </div>

          <!-- SEO: Where to Watch Block -->
          <div class="box seo-box mb-5" style="background-color: var(--background-card);">
            <h2 class="title is-3 has-text-white mb-4">
              <span class="icon-text">
                <span class="icon has-text-danger">
                  <i class="fas fa-tv"></i>
                </span>
                <span>Onde Assistir {{ movie.title }}</span>
              </span>
            </h2>
            <div class="content has-text-white-ter is-size-5">
              
              <!-- Where to Watch - JustWatch Data (prioridade) -->
              <div v-if="hasJustWatchData()" class="mt-4">
                
                <!-- Assinatura (FLATRATE) -->
                <div v-if="flatratePlatforms.length > 0" class="mb-5">
                  <h3 class="title is-5 has-text-white mb-3">
                    <span class="icon-text">
                      <span class="icon has-text-success">
                        <i class="fas fa-play-circle"></i>
                      </span>
                      <span>Disponível em Assinatura</span>
                    </span>
                  </h3>
                  <div class="platform-grid">
                    <a 
                      v-for="(platform, index) in flatratePlatforms" 
                      :key="`flatrate-${index}`"
                      :href="platform.url" 
                      target="_blank"
                      class="platform-card"
                      :title="`Assistir ${movie.title} em ${platform.platform}`"
                    >
                      <div class="platform-icon">
                        <img 
                          :src="platform.icon" 
                          :alt="platform.platform"
                          @error="handleImageError"
                        >
                      </div>
                      <div class="platform-info">
                        <span class="platform-name">{{ platform.platform }}</span>
                        <span class="platform-quality">{{ formatQuality(platform.quality) }}</span>
                      </div>
                    </a>
                  </div>
                </div>

                <!-- Alugar (RENT) -->
                <div v-if="rentPlatforms.length > 0" class="mb-5">
                  <h3 class="title is-5 has-text-white mb-3">
                    <span class="icon-text">
                      <span class="icon has-text-warning">
                        <i class="fas fa-ticket-alt"></i>
                      </span>
                      <span>Disponível para Alugar</span>
                    </span>
                  </h3>
                  <div class="platform-grid">
                    <a 
                      v-for="(platform, index) in rentPlatforms" 
                      :key="`rent-${index}`"
                      :href="platform.url" 
                      target="_blank"
                      class="platform-card"
                      :title="`Alugar ${movie.title} em ${platform.platform}${platform.price ? ' por ' + formatPrice(platform.price) : ''}`"
                    >
                      <div class="platform-icon">
                        <img 
                          :src="platform.icon" 
                          :alt="platform.platform"
                          @error="handleImageError"
                        >
                      </div>
                      <div class="platform-info">
                        <span class="platform-name">{{ platform.platform }}</span>
                        <div class="platform-details">
                          <span class="platform-quality">{{ formatQuality(platform.quality) }}</span>
                          <span v-if="platform.price" class="platform-price">{{ formatPrice(platform.price) }}</span>
                        </div>
                      </div>
                    </a>
                  </div>
                </div>

                <!-- Comprar (BUY) -->
                <div v-if="buyPlatforms.length > 0" class="mb-5">
                  <h3 class="title is-5 has-text-white mb-3">
                    <span class="icon-text">
                      <span class="icon has-text-info">
                        <i class="fas fa-shopping-cart"></i>
                      </span>
                      <span>Disponível para Comprar</span>
                    </span>
                  </h3>
                  <div class="platform-grid">
                    <a 
                      v-for="(platform, index) in buyPlatforms" 
                      :key="`buy-${index}`"
                      :href="platform.url" 
                      target="_blank"
                      class="platform-card"
                      :title="`Comprar ${movie.title} em ${platform.platform}${platform.price ? ' por ' + formatPrice(platform.price) : ''}`"
                    >
                      <div class="platform-icon">
                        <img 
                          :src="platform.icon" 
                          :alt="platform.platform"
                          @error="handleImageError"
                        >
                      </div>
                      <div class="platform-info">
                        <span class="platform-name">{{ platform.platform }}</span>
                        <div class="platform-details">
                          <span class="platform-quality">{{ formatQuality(platform.quality) }}</span>
                          <span v-if="platform.price" class="platform-price">{{ formatPrice(platform.price) }}</span>
                        </div>
                      </div>
                    </a>
                  </div>
                </div>

                <p class="mt-4 is-size-6 has-text-grey-light">
                  <i class="fas fa-info-circle has-text-white"></i>
                  <em> Disponibilidade verificada para Brasil. Clique para acessar diretamente na plataforma.</em>
                </p>
              </div>

              <!-- No data available -->
              <div v-else class="mt-4">
                <div class="notification is-dark">
                  <p class="has-text-centered has-text-white-ter">
                    <i class="fas fa-info-circle mr-2 has-text-white"></i>
                    Nenhuma plataforma de streaming disponível no momento para este filme no Brasil.
                  </p>
                  <p class="has-text-centered is-size-7 mt-2 has-text-grey-light">
                    Verifique novamente em breve ou consulte outras plataformas.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Cast & Crew Section -->
          <div v-if="movie.cast && movie.cast.length" class="mb-6">
            <h3 class="title is-3 has-text-white mb-4">
              <span class="icon-text">
                <span class="icon has-text-danger">
                  <i class="fas fa-user-friends"></i>
                </span>
                <span>Elenco Principal</span>
              </span>
            </h3>
            <div v-if="isMobile" class="horizontal-scroll">
              <div v-for="actor in movie.cast" :key="actor.id" class="horizontal-scroll-item cast-card">
                <a :href="`https://www.themoviedb.org/person/${actor.id}`" target="_blank">
                  <div class="card" style="background-color: var(--background-card);">
                    <div class="card-image">
                      <figure class="image is-square">
                        <img :src="getActorPhoto(actor.profile_path)" :alt="actor.name">
                      </figure>
                    </div>
                    <div class="card-content">
                      <p class="has-text-white has-text-weight-bold">{{ actor.name }}</p>
                      <p class="has-text-white-ter is-size-7">{{ actor.character }}</p>
                    </div>
                  </div>
                </a>
              </div>
            </div>
            <div v-else class="columns is-multiline">
              <div v-for="actor in movie.cast" :key="actor.id" class="column is-one-fifth-desktop is-one-third-tablet is-half-mobile">
                <a :href="`https://www.themoviedb.org/person/${actor.id}`" target="_blank" class="cast-card">
                  <div class="card" style="background-color: var(--background-card);">
                    <div class="card-image">
                      <figure class="image is-square">
                        <img :src="getActorPhoto(actor.profile_path)" :alt="actor.name">
                      </figure>
                    </div>
                    <div class="card-content">
                      <p class="has-text-white has-text-weight-bold">{{ actor.name }}</p>
                      <p class="has-text-white-ter is-size-7">{{ actor.character }}</p>
                    </div>
                  </div>
                </a>
              </div>
            </div>
          </div>

          <!-- Crew -->
          <div v-if="movie.crew && movie.crew.length" class="mb-6">
            <h3 class="title is-3 has-text-white mb-4">
              <span class="icon-text">
                <span class="icon has-text-danger">
                  <i class="fas fa-film"></i>
                </span>
                <span>Equipe Técnica</span>
              </span>
            </h3>
            <div v-if="isMobile" class="horizontal-scroll">
              <div v-for="member in movie.crew" :key="member.id + member.job" class="horizontal-scroll-item crew-card">
                <a :href="`https://www.themoviedb.org/person/${member.id}`" target="_blank">
                  <div class="box" style="background-color: var(--background-card); min-width: 200px;">
                    <p class="has-text-white has-text-weight-bold">{{ member.name }}</p>
                    <p class="has-text-danger">{{ translateCrewRole(member.job) }}</p>
                    <p class="has-text-white-ter is-size-7">{{ member.department }}</p>
                  </div>
                </a>
              </div>
            </div>
            <div v-else class="columns is-multiline">
              <div v-for="member in movie.crew" :key="member.id + member.job" class="column is-one-quarter">
                <a :href="`https://www.themoviedb.org/person/${member.id}`" target="_blank" class="crew-card">
                  <div class="box" style="background-color: var(--background-card);">
                    <p class="has-text-white has-text-weight-bold">{{ member.name }}</p>
                    <p class="has-text-danger">{{ translateCrewRole(member.job) }}</p>
                    <p class="has-text-white-ter is-size-7">{{ member.department }}</p>
                  </div>
                </a>
              </div>
            </div>
          </div>

          <!-- Reviews -->
          <div v-if="movie.reviews_data && movie.reviews_data.length" class="mb-6">
            <h3 class="title is-3 has-text-white mb-4">
              <span class="icon-text">
                <span class="icon has-text-danger">
                  <i class="fas fa-comments"></i>
                </span>
                <span>Avaliações</span>
              </span>
            </h3>
            <div class="reviews-container">
              <div v-for="review in movie.reviews_data" :key="review.id" class="box review-card mb-4" style="background-color: var(--background-card);">
                <article class="media">
                  <figure class="media-left">
                    <p class="image is-64x64">
                      <img 
                        class="is-rounded" 
                        :src="getReviewerAvatar(review.author_details)" 
                        :alt="review.author"
                      >
                    </p>
                  </figure>
                  <div class="media-content">
                    <div class="content">
                      <div class="mb-2">
                        <strong class="has-text-white">{{ review.author_details?.name || review.author }}</strong>
                        <span v-if="review.author_details?.username" class="has-text-white-ter is-size-7 ml-2">
                          @{{ review.author_details.username }}
                        </span>
                        <br>
                        <span v-if="review.author_details?.rating" class="tag is-danger is-light mt-2">
                          <span class="icon">
                            <i class="fas fa-star"></i>
                          </span>
                          <span>{{ review.author_details.rating }}/10</span>
                        </span>
                        <small class="has-text-white-ter ml-2">{{ formatReviewDate(review.created_at) }}</small>
                      </div>
                      <div 
                        class="has-text-white review-content" 
                        :class="{ 'is-collapsed': !review.expanded && isReviewLong(review.content) }"
                        v-html="formatReviewContent(review.content, review.expanded)"
                      ></div>
                      <button 
                        v-if="isReviewLong(review.content)"
                        @click="toggleReview(review)"
                        class="button is-small is-text has-text-danger mt-2"
                      >
                        {{ review.expanded ? 'Ver menos' : 'Ver mais' }}
                      </button>
                    </div>
                  </div>
                </article>
              </div>
            </div>
          </div>

          <!-- Videos Section -->
          <div v-if="hasVideos()" class="mb-6">
            <div v-for="(videos, type) in groupedVideos" :key="type" class="mb-5">
              <h3 class="title is-3 has-text-white mb-4">
                <span class="icon-text">
                  <span class="icon has-text-danger">
                    <i class="fas fa-play-circle"></i>
                  </span>
                  <span>{{ type }}</span>
                </span>
              </h3>
              <div v-if="isMobile" class="horizontal-scroll">
                <div v-for="video in videos" :key="video.key" class="horizontal-scroll-item video-card">
                  <div class="box video-box" style="background-color: var(--background-card);">
                    <div class="video-thumbnail" @click="openVideo(video.url)">
                      <img :src="`https://img.youtube.com/vi/${video.key}/mqdefault.jpg`" :alt="video.name">
                      <div class="play-overlay">
                        <span class="icon is-large has-text-danger">
                          <i class="fas fa-play-circle fa-3x"></i>
                        </span>
                      </div>
                    </div>
                    <p class="has-text-white mt-2">{{ video.name }}</p>
                    <p class="has-text-white-ter is-size-7">
                      {{ video.official ? '✓ Oficial' : '' }}
                    </p>
                  </div>
                </div>
              </div>
              <div v-else class="columns is-multiline">
                <div v-for="video in videos" :key="video.key" class="column is-one-third">
                  <div class="box video-box" style="background-color: var(--background-card);">
                    <div class="video-thumbnail" @click="openVideo(video.url)">
                      <img :src="`https://img.youtube.com/vi/${video.key}/mqdefault.jpg`" :alt="video.name">
                      <div class="play-overlay">
                        <span class="icon is-large has-text-danger">
                          <i class="fas fa-play-circle fa-3x"></i>
                        </span>
                      </div>
                    </div>
                    <p class="has-text-white mt-2">{{ video.name }}</p>
                    <p class="has-text-white-ter is-size-7">
                      {{ video.official ? '✓ Oficial' : '' }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Photos Section -->
          <div v-if="hasPhotos()" class="mb-6">
            <div v-if="uniqueBackdrops.length">
              <h3 class="title is-3 has-text-white mb-4">
                <span class="icon-text">
                  <span class="icon has-text-danger">
                    <i class="fas fa-images"></i>
                  </span>
                  <span>Galeria</span>
                </span>
              </h3>
              <div v-if="isMobile" class="horizontal-scroll">
                <div v-for="(image, index) in uniqueBackdrops" :key="'backdrop-' + index" class="horizontal-scroll-item image-card">
                  <figure class="image is-16by9 photo-item" @click="openLightbox(image.url)">
                    <img :src="image.url" :alt="`${movie.title} - Imagem ${index + 1}`">
                  </figure>
                </div>
              </div>
              <div v-else class="columns is-multiline">
                <div v-for="(image, index) in uniqueBackdrops" :key="'backdrop-' + index" class="column is-one-third">
                  <figure class="image is-16by9 photo-item" @click="openLightbox(image.url)">
                    <img :src="image.url" :alt="`${movie.title} - Imagem ${index + 1}`">
                  </figure>
                </div>
              </div>
            </div>

            <div v-if="uniquePosters.length" class="mt-5">
              <h3 class="title is-3 has-text-white mb-4">
                <span class="icon-text">
                  <span class="icon has-text-danger">
                    <i class="fas fa-image"></i>
                  </span>
                  <span>Pôsteres</span>
                </span>
              </h3>
              <div v-if="isMobile" class="horizontal-scroll">
                <div v-for="(image, index) in uniquePosters" :key="'poster-' + index" class="horizontal-scroll-item image-card">
                  <figure class="image photo-item" @click="openLightbox(image.url)" style="width: 200px;">
                    <img :src="image.url" :alt="`${movie.title} - Pôster ${index + 1}`">
                  </figure>
                </div>
              </div>
              <div v-else class="columns is-multiline">
                <div v-for="(image, index) in uniquePosters" :key="'poster-' + index" class="column is-one-quarter">
                  <figure class="image photo-item" @click="openLightbox(image.url)">
                    <img :src="image.url" :alt="`${movie.title} - Pôster ${index + 1}`">
                  </figure>
                </div>
              </div>
            </div>
          </div>

          <!-- Technical Details Section -->
          <div class="mb-6">
            <div class="box" style="background-color: var(--background-card);">
              <h3 class="title is-3 has-text-white mb-4">
                <span class="icon-text">
                  <span class="icon has-text-danger">
                    <i class="fas fa-info-circle"></i>
                  </span>
                  <span>Detalhes Técnicos</span>
                </span>
              </h3>
              <table class="table is-fullwidth" style="background-color: transparent;">
                <tbody>
                  <tr>
                    <td class="has-text-white has-text-weight-bold">Nacionalidade</td>
                    <td class="has-text-white-ter">{{ movie ? getCountries() : '-' }}</td>
                  </tr>
                  <tr>
                    <td class="has-text-white has-text-weight-bold">Distribuidor</td>
                    <td class="has-text-white-ter">{{ movie ? getDistributors() : '-' }}</td>
                  </tr>
                  <tr>
                    <td class="has-text-white has-text-weight-bold">Ano de produção</td>
                    <td class="has-text-white-ter">{{ movie ? getYear() : '-' }}</td>
                  </tr>
                  <tr>
                    <td class="has-text-white has-text-weight-bold">Tipo de filme</td>
                    <td class="has-text-white-ter">Longa-metragem</td>
                  </tr>
                  <tr>
                    <td class="has-text-white has-text-weight-bold">Orçamento</td>
                    <td class="has-text-white-ter">{{ movie ? getBudget() : '-' }}</td>
                  </tr>
                  <tr>
                    <td class="has-text-white has-text-weight-bold">Receita</td>
                    <td class="has-text-white-ter">{{ movie ? getRevenue() : '-' }}</td>
                  </tr>
                  <tr>
                    <td class="has-text-white has-text-weight-bold">Idioma Original</td>
                    <td class="has-text-white-ter">{{ movie ? getLanguage() : '-' }}</td>
                  </tr>
                  <tr>
                    <td class="has-text-white has-text-weight-bold">Duração</td>
                    <td class="has-text-white-ter">{{ movie.runtime ? movie.runtime + ' minutos' : '-' }}</td>
                  </tr>
                  <tr>
                    <td class="has-text-white has-text-weight-bold">Cor</td>
                    <td class="has-text-white-ter">Colorido</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Back Button -->
          <div class="has-text-centered mt-6">
            <button @click="goBack" class="button is-primary is-medium">
              <span class="icon">
                <i class="fas fa-arrow-left"></i>
              </span>
              <span>Voltar</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else class="empty-state">
      <span class="icon">
        <i class="fas fa-exclamation-triangle"></i>
      </span>
      <p>Filme não encontrado</p>
      <button @click="goBack" class="button is-primary mt-4">Voltar</button>
    </div>

    <!-- Lightbox Modal -->
    <div class="modal" :class="{ 'is-active': lightboxImage }">
      <div class="modal-background" @click="closeLightbox"></div>
      <div class="modal-content">
        <p class="image">
          <img :src="lightboxImage" alt="Imagem ampliada">
        </p>
      </div>
      <button class="modal-close is-large" @click="closeLightbox"></button>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useMovieStore } from '../store/movie.js'
import { useHead } from '../composables/useHead.js'
import { useIsMobile } from '../composables/useMediaQuery.js'
import { translateCrewRole as translateRole, translateCountry, getCountryName, getCountryFlag } from '../utils/translations.js'
import { getPlatformUrl } from '../utils/platformUrls.js'
import RatingBadge from '../components/RatingBadge.vue'

export default {
  name: 'MovieDetail',
  components: {
    RatingBadge
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    const store = useMovieStore()
    const loading = ref(true)
    const movie = ref(null)
    const activeTab = ref('cast')
    const lightboxImage = ref(null)
    const isMobile = useIsMobile()

    const getCountryFlagUrl = (country) => {
      const flagUrls = {
        'United States of America': 'https://flagcdn.com/w40/us.png',
        'United Kingdom': 'https://flagcdn.com/w40/gb.png',
        'Brazil': 'https://flagcdn.com/w40/br.png',
        'France': 'https://flagcdn.com/w40/fr.png',
        'Germany': 'https://flagcdn.com/w40/de.png',
        'Italy': 'https://flagcdn.com/w40/it.png',
        'Spain': 'https://flagcdn.com/w40/es.png',
        'Mexico': 'https://flagcdn.com/w40/mx.png',
        'Canada': 'https://flagcdn.com/w40/ca.png',
        'Japan': 'https://flagcdn.com/w40/jp.png',
        'South Korea': 'https://flagcdn.com/w40/kr.png',
        'China': 'https://flagcdn.com/w40/cn.png',
        'India': 'https://flagcdn.com/w40/in.png',
        'Australia': 'https://flagcdn.com/w40/au.png',
        'New Zealand': 'https://flagcdn.com/w40/nz.png',
        'Argentina': 'https://flagcdn.com/w40/ar.png',
        'Russia': 'https://flagcdn.com/w40/ru.png',
        'Sweden': 'https://flagcdn.com/w40/se.png',
        'Norway': 'https://flagcdn.com/w40/no.png',
        'Denmark': 'https://flagcdn.com/w40/dk.png',
        'Finland': 'https://flagcdn.com/w40/fi.png',
        'Netherlands': 'https://flagcdn.com/w40/nl.png',
        'Belgium': 'https://flagcdn.com/w40/be.png',
        'Switzerland': 'https://flagcdn.com/w40/ch.png',
        'Austria': 'https://flagcdn.com/w40/at.png',
        'Poland': 'https://flagcdn.com/w40/pl.png',
        'Portugal': 'https://flagcdn.com/w40/pt.png',
        'Ireland': 'https://flagcdn.com/w40/ie.png',
        'Czech Republic': 'https://flagcdn.com/w40/cz.png',
        'Hungary': 'https://flagcdn.com/w40/hu.png',
        'Romania': 'https://flagcdn.com/w40/ro.png',
        'Turkey': 'https://flagcdn.com/w40/tr.png',
        'Greece': 'https://flagcdn.com/w40/gr.png',
        'Thailand': 'https://flagcdn.com/w40/th.png',
        'Indonesia': 'https://flagcdn.com/w40/id.png',
        'Philippines': 'https://flagcdn.com/w40/ph.png',
        'Vietnam': 'https://flagcdn.com/w40/vn.png',
        'Hong Kong': 'https://flagcdn.com/w40/hk.png',
        'Taiwan': 'https://flagcdn.com/w40/tw.png',
        'Singapore': 'https://flagcdn.com/w40/sg.png',
        'Malaysia': 'https://flagcdn.com/w40/my.png',
        'Chile': 'https://flagcdn.com/w40/cl.png',
        'Colombia': 'https://flagcdn.com/w40/co.png',
        'Peru': 'https://flagcdn.com/w40/pe.png',
        'Venezuela': 'https://flagcdn.com/w40/ve.png',
        'Uruguay': 'https://flagcdn.com/w40/uy.png',
        'South Africa': 'https://flagcdn.com/w40/za.png',
        'Egypt': 'https://flagcdn.com/w40/eg.png',
        'Israel': 'https://flagcdn.com/w40/il.png',
        'Saudi Arabia': 'https://flagcdn.com/w40/sa.png',
        'United Arab Emirates': 'https://flagcdn.com/w40/ae.png'
      }
      return flagUrls[country] || null
    }

    const translateCrewRole = (role) => {
      return translateRole(role)
    }

    const getStreamingUrl = (platform) => {
      if (!platform || !movie.value) return '#'
      return getPlatformUrl(platform.name, movie.value.title, platform.web_url)
    }

    const loadMovie = async () => {
      try {
        loading.value = true
        await store.fetchMovie(route.params.slug)
        movie.value = store.currentMovie
        
        if (movie.value) {
          // Debug sinopse
          if (movie.value.ai_content && movie.value.ai_content.ai_synopsis) {
          } else {
          }
          
          // Temporariamente desabilitado para debug
          // updateMetaTags()
        } else {
        }
      } catch (error) {
        console.error('Erro ao carregar filme:', error)
      } finally {
        loading.value = false
      }
    }

    const updateMetaTags = () => {
      const m = movie.value
      if (!m) {
        return
      }


      try {
        const keywords = [
          m.title,
          `${m.title} data de lançamento`,
          `${m.title} elenco`,
          `${m.title} atores`,
          `${m.title} trailer`,
          `${m.title} sinopse`,
          `${m.title} onde assistir`,
          ...(m.genres || [])
        ].join(', ')

        useHead({
          title: `${m.title} (${getYear()}) - Data de Lançamento, Elenco e Trailer | Guia de Filmes`,
          meta: [
            { name: 'description', content: `${m.title} estreia em ${formatDate(m.release_date)}. Veja elenco completo, trailer, sinopse e onde assistir. ${m.synopsis?.substring(0, 100) || ''}` },
            { name: 'keywords', content: keywords },
            { property: 'og:title', content: `${m.title} (${getYear()})` },
            { property: 'og:description', content: m.synopsis?.substring(0, 200) || 'Veja detalhes completos' },
            { property: 'og:type', content: 'video.movie' },
            { property: 'og:url', content: window.location.href },
            { property: 'og:image', content: getPosterUrl() },
            { name: 'twitter:card', content: 'summary_large_image' },
            { name: 'twitter:title', content: `${m.title} (${getYear()})` },
            { name: 'twitter:description', content: m.synopsis?.substring(0, 200) || '' },
            { name: 'twitter:image', content: getPosterUrl() }
          ],
          script: [
            {
              type: 'application/ld+json',
              innerHTML: JSON.stringify({
                '@context': 'https://schema.org',
                '@type': 'Movie',
                name: m.title,
                description: m.synopsis || '',
                image: getPosterUrl(),
                datePublished: m.release_date,
                genre: m.genres || [],
                actor: (m.cast || []).map(actor => ({
                  '@type': 'Person',
                  name: actor.name,
                  url: `https://www.themoviedb.org/person/${actor.id}`
                })),
                director: (m.crew || []).filter(c => c.job === 'Director').map(director => ({
                  '@type': 'Person',
                  name: director.name
                })),
                aggregateRating: m.tmdb_rating ? {
                  '@type': 'AggregateRating',
                  ratingValue: m.tmdb_rating,
                  ratingCount: m.tmdb_vote_count
                } : undefined,
                duration: m.runtime ? `PT${m.runtime}M` : undefined
              })
            }
          ]
        })
      } catch (error) {
        console.error('Erro ao atualizar meta tags:', error)
      }
    }

    const getSynopsis = () => {
      return movie.value?.synopsis || 'Sinopse não disponível'
    }

    const getGenreLink = (genre) => {
      const genreMap = {
        'Ação': 'acao',
        'Aventura': 'aventura',
        'Comédia': 'comedia',
        'Drama': 'drama',
        'Ficção científica': 'ficcao-cientifica',
        'Terror': 'terror',
        'Romance': 'romance',
        'Suspense': 'suspense',
        'Animação': 'animacao',
        'Crime': 'crime',
        'Documentário': 'documentario',
        'Família': 'familia',
        'Fantasia': 'fantasia',
        'Guerra': 'guerra',
        'História': 'historia',
        'Mistério': 'misterio',
        'Musical': 'musical',
        'Western': 'western',
      }
      const slug = genreMap[genre] || genre.toLowerCase().replace(/\s+/g, '-')
      return `/explorar/genero/${slug}`
    }

    const getPosterUrl = () => {
      if (!movie.value) {
        return 'https://via.placeholder.com/500x750/1a1a1a/e50914?text=SEM+POSTER'
      }
      
      if (movie.value.poster_url) {
        return movie.value.poster_url
      }
      // Imagem default para filmes sem poster
      return 'https://via.placeholder.com/500x750/1a1a1a/e50914?text=SEM+POSTER'
    }

    const getBackdropUrl = () => {
      if (!movie.value) {
        return 'url(https://via.placeholder.com/1920x1080/1a1a1a/e50914?text=GUIA+DE+FILMES)'
      }
      
      if (movie.value.images && movie.value.images.backdrops && movie.value.images.backdrops.length > 0) {
        return `url(${movie.value.images.backdrops[0].url})`
      }
      if (movie.value.backdrop_url) {
        return `url(${movie.value.backdrop_url})`
      }
      if (movie.value.poster_url) {
        return `url(${movie.value.poster_url})`
      }
      return 'url(https://via.placeholder.com/1920x1080/1a1a1a/e50914?text=GUIA+DE+FILMES)'
    }

    const getActorPhoto = (profilePath) => {
      if (profilePath) {
        return profilePath
      }
      // Placeholder SVG melhor para atores
      return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="185" height="278" viewBox="0 0 185 278"%3E%3Crect width="185" height="278" fill="%231a1a1a"/%3E%3Ccircle cx="92.5" cy="80" r="35" fill="%23666"/%3E%3Cpath d="M30 190 Q30 140 92.5 140 Q155 140 155 190 L155 278 L30 278 Z" fill="%23666"/%3E%3Ctext x="92.5" y="250" font-family="Arial" font-size="14" fill="%23999" text-anchor="middle"%3ESEM FOTO%3C/text%3E%3C/svg%3E'
    }

    const getReviewerAvatar = (authorDetails) => {
      if (authorDetails?.avatar_path) {
        // Se começar com /, é da TMDB - usar o padrão correto
        if (authorDetails.avatar_path.startsWith('/')) {
          return `https://media.themoviedb.org/t/p/w150_and_h150_face${authorDetails.avatar_path}`
        }
        // Se for URL do Gravatar
        if (authorDetails.avatar_path.includes('gravatar')) {
          return authorDetails.avatar_path.replace('/https:', 'https:')
        }
        return authorDetails.avatar_path
      }
      // Avatar padrão
      return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64"%3E%3Crect width="64" height="64" fill="%231a1a1a"/%3E%3Ccircle cx="32" cy="24" r="12" fill="%23666"/%3E%3Cpath d="M10 55 Q10 38 32 38 Q54 38 54 55 L54 64 L10 64 Z" fill="%23666"/%3E%3C/svg%3E'
    }

    const formatReviewDate = (dateString) => {
      if (!dateString) return ''
      const date = new Date(dateString)
      const options = { year: 'numeric', month: 'long', day: 'numeric' }
      return date.toLocaleDateString('pt-BR', options)
    }

    const isReviewLong = (content) => {
      if (!content) return false
      return content.length > 600
    }

    const toggleReview = (review) => {
      review.expanded = !review.expanded
    }

    const formatReviewContent = (content, expanded = false) => {
      if (!content) return ''
      
      let text = content
      const maxLength = 600
      
      // Se não está expandido e o texto é longo, corta
      if (!expanded && text.length > maxLength) {
        text = text.substring(0, maxLength) + '...'
      }
      
      // Converte quebras de linha em parágrafos
      text = text.replace(/\r\n\r\n/g, '</p><p>')
      text = text.replace(/\n\n/g, '</p><p>')
      text = text.replace(/\r\n/g, '<br>')
      text = text.replace(/\n/g, '<br>')
      
      return `<p>${text}</p>`
    }

    const formatNumber = (num) => {
      if (!num) return '0'
      return new Intl.NumberFormat('pt-BR').format(num)
    }

    const getYearContext = () => {
      const year = getYear()
      if (year === '-') return ''
      return ` (${year})`
    }

    const getReleaseContext = () => {
      const status = movie.value.status
      const title = movie.value.title
      
      if (status === 'upcoming') {
        return `Este é um dos lançamentos mais aguardados de ${getYear()}. Marque na sua agenda e não perca a estreia de ${title} nos cinemas!`
      } else if (status === 'in_theaters') {
        return `${title} está fazendo sucesso nas bilheterias. Garanta seus ingressos e não fique de fora desta experiência cinematográfica única.`
      } else {
        return `${title} marcou sua estreia em ${formatDate(movie.value.release_date)} e já conquistou milhares de espectadores ao redor do mundo.`
      }
    }

    const formatDate = (date) => {
      if (!date) return 'Data não disponível'
      const options = { year: 'numeric', month: 'long', day: 'numeric' }
      return new Date(date).toLocaleDateString('pt-BR', options)
    }

    const formatStatus = (status) => {
      const statuses = {
        'upcoming': 'Próxima Estreia',
        'in_theaters': 'Em Cartaz',
        'released': 'Lançado'
      }
      return statuses[status] || status
    }

    const getReleaseDateText = () => {
      const status = movie.value.status
      if (status === 'upcoming') {
        return 'estreia em'
      } else if (status === 'in_theaters') {
        return 'está em cartaz desde'
      } else {
        return 'foi lançado em'
      }
    }

    const getReleaseBlockTitle = () => {
      const status = movie.value.status
      if (status === 'upcoming') {
        return `Quando ${movie.value.title} vai estrear?`
      } else if (status === 'in_theaters') {
        return `Quando e onde ${movie.value.title} está disponível?`
      } else {
        return `Quando ${movie.value.title} foi lançado?`
      }
    }

    const getAvailabilityText = () => {
      const status = movie.value.status
      if (status === 'upcoming') {
        return 'Fique atento para não perder a estreia nos cinemas!'
      } else if (status === 'in_theaters') {
        return 'O filme está disponível nos cinemas. Confira as sessões mais próximas de você.'
      } else {
        return 'Confira abaixo onde assistir este filme em streaming.'
      }
    }

    const getWhereToWatchText = () => {
      const status = movie.value.status
      const title = movie.value.title
      
      if (status === 'upcoming') {
        return `${title} ainda não foi lançado. A estreia está prevista para ${formatDate(movie.value.release_date)}. 
                Assim que o filme estiver disponível nos cinemas, você poderá conferir as sessões em sua cidade.`
      } else if (status === 'in_theaters') {
        return `${title} está em cartaz nos cinemas! Confira as sessões disponíveis em sua cidade e garanta seu ingresso. 
                O filme pode estar disponível em redes como Cinemark, Cinépolis, UCI e outros cinemas independentes.`
      } else {
        return `${title} já saiu de cartaz, mas você pode encontrá-lo em plataformas de streaming como Netflix, 
                Amazon Prime Video, Disney+, HBO Max, Apple TV+ ou alugar/comprar em serviços digitais. 
                Consulte a disponibilidade nas plataformas.`
      }
    }

    const getCastPreview = () => {
      if (!movie.value.cast || movie.value.cast.length === 0) return ''
      const names = movie.value.cast.slice(0, 3).map(actor => actor.name)
      return names.join(', ')
    }

    const getCountries = () => {
      if (movie.value.production_countries && movie.value.production_countries.length > 0) {
        const filtered = movie.value.production_countries
          .map(c => {
            const countryName = getCountryName(c.name)
            const flagUrl = getCountryFlagUrl(c.name)
            // return flagUrl ? `<img src="${flagUrl}" alt="${countryName}" style="width: 20px; height: 15px; margin-right: 5px; border-radius: 2px;"> ${countryName}` : countryName
            return countryName
          })
          .filter(name => name && name.trim() !== '')
        return filtered.length > 0 ? filtered.join(', ') : '-'
      }
      return '-'
    }

    const getDistributors = () => {
      if (movie.value.production_companies && movie.value.production_companies.length > 0) {
        const filtered = movie.value.production_companies
          .map(c => c.name)
          .filter(name => name && name.trim() !== '')
        return filtered.length > 0 ? filtered.join(', ') : '-'
      }
      return '-'
    }

    const getYear = () => {
      if (!movie.value?.release_date) {
        return '-'
      }
      
      const year = new Date(movie.value.release_date).getFullYear()
      return year
    }

    const formatCurrency = (value) => {
      if (!value || value <= 0) return '-'
      
      const millions = value / 1000000
      if (millions >= 1) {
        return `US$ ${millions.toFixed(1).replace('.', ',')} milhões`
      }
      
      const thousands = value / 1000
      if (thousands >= 1) {
        return `US$ ${thousands.toFixed(0)} mil`
      }
      
      return `US$ ${value.toLocaleString('pt-BR')}`
    }

    const getBudget = () => {
      return formatCurrency(movie.value.budget)
    }

    const getRevenue = () => {
      return formatCurrency(movie.value.revenue)
    }

    const getLanguage = () => {
      const languages = {
        'en': 'Inglês',
        'pt': 'Português',
        'es': 'Espanhol',
        'fr': 'Francês',
        'de': 'Alemão',
        'it': 'Italiano',
        'ja': 'Japonês',
        'ko': 'Coreano',
        'zh': 'Chinês',
        'ar': 'Árabe',
        'ru': 'Russo',
        'hi': 'Hindi',
        'nl': 'Holandês',
        'sv': 'Sueco',
        'no': 'Norueguês',
        'da': 'Dinamarquês',
        'fi': 'Finlandês',
        'pl': 'Polonês',
        'cs': 'Tcheco',
        'hu': 'Húngaro',
        'ro': 'Romeno',
        'tr': 'Turco',
        'th': 'Tailandês',
        'vi': 'Vietnamita',
        'he': 'Hebraico',
        'id': 'Indonésio',
        'ms': 'Malaio',
        'uk': 'Ucraniano',
        'el': 'Grego',
        'fa': 'Persa',
        'bn': 'Bengali',
        'ta': 'Tâmil',
        'te': 'Telugu'
      }
      return languages[movie.value.original_language] || movie.value.original_language || '-'
    }

    const hasVideos = () => {
      return movie.value.videos && movie.value.videos.length > 0
    }

    const hasPhotos = () => {
      return movie.value.images && 
             ((movie.value.images.backdrops && movie.value.images.backdrops.filter(img => img.url).length > 0) ||
              (movie.value.images.posters && movie.value.images.posters.filter(img => img.url).length > 0))
    }

    const groupedVideos = computed(() => {
      if (!movie.value?.videos) {
        return {}
      }
      
      const groups = {}
      const typeTranslation = {
        'Trailer': 'Trailers',
        'Teaser': 'Teasers',
        'Clip': 'Clipes',
        'Behind the Scenes': 'Bastidores',
        'Featurette': 'Making Of'
      }

      movie.value.videos.forEach(video => {
        const type = typeTranslation[video.type] || video.type
        if (!groups[type]) {
          groups[type] = []
        }
        groups[type].push(video)
      })

      return groups
    })

    // Normaliza os dados do JustWatch para um formato único
    const normalizeJustWatchData = () => {
      if (!movie.value || !movie.value.justwatch_watch_info) {
        return []
      }
      
      const data = movie.value.justwatch_watch_info
      if (!Array.isArray(data) || data.length === 0) {
        return []
      }

      // Formato 1: Array direto de plataformas
      // [{ url, icon, type, price, quality, platform }]
      if (data[0].platform && data[0].type) {
        return data
      }

      // Formato 2: Objeto com offers
      // [{ url, title, offers: [{ url, icon, type, price, quality, platform }] }]
      if (data[0].offers && Array.isArray(data[0].offers)) {
        return data[0].offers
      }

      // Se não reconhecer o formato, retorna vazio
      return []
    }

    // Verifica se tem dados do JustWatch
    const hasJustWatchData = () => {
      return normalizeJustWatchData().length > 0
    }

    // Separa plataformas por tipo (JustWatch format)
    const flatratePlatforms = computed(() => {
      const normalized = normalizeJustWatchData()
      return normalized.filter(p => p.type === 'FLATRATE')
    })

    const rentPlatforms = computed(() => {
      const normalized = normalizeJustWatchData()
      return normalized.filter(p => p.type === 'RENT')
    })

    const buyPlatforms = computed(() => {
      const normalized = normalizeJustWatchData()
      return normalized.filter(p => p.type === 'BUY')
    })

    // Formata qualidade (remove _ e torna maiúsculo)
    const formatQuality = (quality) => {
      if (!quality) return 'HD'
      // Remove underscore e torna uppercase
      // Ex: "_4K" vira "4K", "HD" continua "HD"
      return quality.replace('_', '').toUpperCase()
    }

    // Formata preço (de "19,90 R$" para "R$ 19,90")
    const formatPrice = (price) => {
      if (!price) return ''
      // Verifica se está no formato antigo "valor R$"
      const match = price.match(/^(\d+[,.]?\d*)\s*R\$/)
      if (match) {
        return `R$ ${match[1]}`
      }
      // Se já está correto ou outro formato, retorna como está
      return price
    }

    const uniquePosters = computed(() => {
      if (!movie.value?.images?.posters) {
        return []
      }
      
      const seenUrls = new Set()
      return movie.value.images.posters.filter(img => {
        if (!img.url) return false
        if (seenUrls.has(img.url)) return false
        seenUrls.add(img.url)
        return true
      })
    })

    const uniqueBackdrops = computed(() => {
      if (!movie.value?.images?.backdrops) {
        return []
      }
      
      const seenUrls = new Set()
      return movie.value.images.backdrops.filter(img => {
        if (!img.url) return false
        if (seenUrls.has(img.url)) return false
        seenUrls.add(img.url)
        return true
      })
    })

    const handleImageError = (event) => {
      // Fallback for broken images
      event.target.style.display = 'none'
      event.target.nextElementSibling?.classList.add('is-visible')
    }

    const openVideo = (url) => {
      window.open(url, '_blank')
    }

    const openLightbox = (imageUrl) => {
      lightboxImage.value = imageUrl
    }

    const closeLightbox = () => {
      lightboxImage.value = null
    }

    const goBack = () => {
      // Check if there's history to go back to
      if (window.history.length > 1) {
        router.go(-1)
      } else {
        // Fallback to home page
        router.push('/')
      }
    }

    onMounted(() => {
      loadMovie()
    })

    // Watch for route changes
    watch(() => route.params.slug, (newSlug, oldSlug) => {
      if (newSlug && newSlug !== oldSlug) {
        loadMovie()
      }
    })

    return {
      loading,
      movie,
      activeTab,
      lightboxImage,
      isMobile,
      translateCrewRole,
      getStreamingUrl,
      formatDate,
      formatStatus,
      formatNumber,
      getSynopsis,
      getGenreLink,
      getPosterUrl,
      getBackdropUrl,
      getActorPhoto,
      getReviewerAvatar,
      formatReviewDate,
      formatReviewContent,
      isReviewLong,
      toggleReview,
      getReleaseDateText,
      getReleaseBlockTitle,
      getYearContext,
      getReleaseContext,
      getAvailabilityText,
      getWhereToWatchText,
      getCastPreview,
      getCountries,
      getDistributors,
      getYear,
      getBudget,
      getRevenue,
      getLanguage,
      hasVideos,
      hasPhotos,
      groupedVideos,
      hasJustWatchData,
      flatratePlatforms,
      rentPlatforms,
      buyPlatforms,
      formatQuality,
      formatPrice,
      uniquePosters,
      uniqueBackdrops,
      handleImageError,
      openVideo,
      openLightbox,
      closeLightbox,
      goBack
    }
  }
}
</script>

<style scoped>
.movie-detail-header {
  background-size: cover;
  background-position: center top;
  min-height: 600px;
  position: relative;
  margin-top: -52px;
  padding-top: 52px;
}

.movie-detail-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(to bottom, rgba(15, 15, 15, 0.7) 0%, var(--background-dark) 100%);
}

.movie-detail-content {
  position: relative;
  z-index: 1;
  padding-top: 5rem;
  padding-bottom: 3rem;
}

.movie-poster-large {
  border-radius: 8px;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.5);
}

.movie-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: center;
  color: var(--text-muted);
  font-size: 1.1rem;
}

.movie-meta-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.genres {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.tabs {
  margin-top: 2rem;
}

.tabs ul {
  border-bottom-color: var(--primary-color);
}

.tabs a {
  color: var(--text-muted);
  border-bottom-color: transparent;
}

.tabs li.is-active a {
  color: var(--primary-color);
  border-bottom-color: var(--primary-color);
}

.tabs a:hover {
  border-bottom-color: var(--primary-color);
  color: white;
}

.tab-content {
  padding: 2rem 0;
}

.cast-card {
  text-decoration: none;
  display: block;
  transition: transform 0.3s;
}

.cast-card:hover {
  transform: translateY(-5px);
}

.cast-card .card {
  height: 100%;
  border-radius: 8px;
  overflow: hidden;
  transition: box-shadow 0.3s;
}

.cast-card .card:hover {
  box-shadow: 0 4px 12px rgba(229, 9, 20, 0.4);
}

.cast-card .image img {
  object-fit: cover;
  width: 100%;
  height: 100%;
}

/* Reviews */
.review-card {
  border-left: 3px solid var(--danger);
  transition: transform 0.2s, box-shadow 0.2s;
}

.review-card:hover {
  transform: translateX(5px);
  box-shadow: 0 4px 12px rgba(229, 9, 20, 0.3);
}

.review-content {
  line-height: 1.6;
  transition: max-height 0.3s ease;
}

.review-content.is-collapsed {
  max-height: 200px;
  overflow: hidden;
  position: relative;
}

.review-content.is-collapsed::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 60px;
  background: linear-gradient(to bottom, transparent, var(--background-card));
}

.review-content p {
  margin-bottom: 1em;
}

.reviews-container {
  max-height: none;
}

.video-box {
  cursor: pointer;
  transition: transform 0.3s;
}

.video-box:hover {
  transform: translateY(-5px);
}

.video-thumbnail {
  position: relative;
  cursor: pointer;
  border-radius: 8px;
  overflow: hidden;
}

.video-thumbnail img {
  width: 100%;
  display: block;
}

.play-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s;
}

.video-thumbnail:hover .play-overlay {
  opacity: 1;
}

.photo-item {
  cursor: pointer;
  border-radius: 8px;
  overflow: hidden;
  transition: transform 0.3s;
}

.photo-item:hover {
  transform: scale(1.05);
}

.photo-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.table td {
  border-color: rgba(255, 255, 255, 0.1);
  padding: 1rem;
}

.seo-content .box {
  border-left: 4px solid var(--primary-color);
}

.rating-section {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.rating-score {
  color: #f5f5f5 !important;
  font-weight: 700;
}

.seo-box {
  border-left: 4px solid var(--primary-color);
}

/* Platform Grid - Layout limpo e organizado */
.platform-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.platform-card {
  background-color: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  text-decoration: none;
  transition: all 0.3s ease;
  cursor: pointer;
}

.platform-card:hover {
  background-color: rgba(255, 255, 255, 0.1);
  border-color: var(--primary-color);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(229, 9, 20, 0.3);
}

.platform-icon {
  flex-shrink: 0;
  width: 60px;
  height: 60px;
  background-color: white;
  border-radius: 8px;
  padding: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.platform-icon img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.platform-info {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.platform-name {
  color: white;
  font-weight: 600;
  font-size: 0.95rem;
  line-height: 1.3;
}

.platform-details {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.platform-quality {
  background-color: rgba(255, 255, 255, 0.1);
  color: #fff;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.platform-price {
  color: var(--primary-color);
  font-weight: 700;
  font-size: 0.9rem;
}

.platform-fallback {
  background-color: #1a1a1a;
  color: #fff;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 600;
  text-align: center;
  min-width: 100px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.crew-card {
  text-decoration: none;
  display: block;
  transition: transform 0.3s;
}

.crew-card:hover {
  transform: translateY(-3px);
}

.crew-card .box:hover {
  box-shadow: 0 4px 12px rgba(229, 9, 20, 0.3);
}

@media (max-width: 768px) {
  .movie-detail-header {
    min-height: 400px;
  }
  
  .movie-detail-content {
    padding-top: 3rem;
  }

  .movie-meta {
    font-size: 0.9rem;
  }

  .platform-grid {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }

  .platform-card {
    padding: 0.75rem;
  }

  .platform-icon {
    width: 50px;
    height: 50px;
  }

  .platform-name {
    font-size: 0.875rem;
  }

  .rating-section {
    flex-direction: column;
    align-items: flex-start;
  }
}

/* Lightbox/Modal Styles */
.modal .modal-content {
  max-width: 90vw;
  max-height: 90vh;
  width: auto;
  height: auto;
}

.modal .modal-content .image img {
  max-width: 100%;
  max-height: 90vh;
  width: auto;
  height: auto;
  object-fit: contain;
}

@media (min-width: 769px) {
  .modal .modal-content {
    max-width: 1200px;
  }
}
</style>