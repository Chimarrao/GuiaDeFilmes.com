<template>
  <div class="about-page">
    <!-- Hero Section -->
    <section class="hero is-medium is-dark">
      <div class="hero-body">
        <div class="container has-text-centered">
          <p class="title is-1 has-text-white">
            <span class="icon-text">
              <span class="icon has-text-danger">
                <i class="fas fa-info-circle"></i>
              </span>
              <span>Sobre o CineRadar</span>
            </span>
          </p>
          <p class="subtitle is-4 has-text-white-ter">
            Seu radar cinematográfico completo
          </p>
        </div>
      </div>
    </section>

    <!-- Content -->
    <section class="section">
      <div class="container">
        <!-- Project Description -->
        <div class="box" style="background-color: var(--background-card);">
          <h2 class="title is-3 has-text-white">
            <span class="icon-text">
              <span class="icon has-text-danger">
                <i class="fas fa-film"></i>
              </span>
              <span>O Projeto</span>
            </span>
          </h2>
          <div class="content has-text-white-ter is-size-5">
            <p>
              O <strong class="has-text-danger">CineRadar</strong> é sua plataforma completa para descobrir os melhores filmes! 🎬
            </p>
            <p>
              Aqui você encontra tudo que precisa para se manter atualizado sobre o mundo do cinema:
            </p>
            <ul>
              <li>📽️ <strong>Próximas Estreias</strong> - Fique por dentro dos filmes que estão chegando aos cinemas</li>
              <li>🎟️ <strong>Em Cartaz</strong> - Veja quais filmes estão passando agora nos cinemas</li>
              <li>🍿 <strong>Lançamentos Recentes</strong> - Descubra os filmes que acabaram de estrear</li>
              <li>⭐ <strong>Avaliações e Críticas</strong> - Confira as opiniões sobre cada filme</li>
              <li>🎥 <strong>Trailers</strong> - Assista aos trailers oficiais direto no site</li>
              <li>👥 <strong>Elenco e Equipe</strong> - Conheça os atores e diretores dos seus filmes favoritos</li>
            </ul>
            <p>
              Nossa missão é tornar sua experiência cinematográfica mais rica e completa, reunindo todas as informações 
              que você precisa em um só lugar, com uma interface moderna, intuitiva e totalmente responsiva para todos os dispositivos.
            </p>
          </div>
        </div>

        <!-- Why Choose Us -->
        <div class="box mt-5" style="background-color: var(--background-card);">
          <h2 class="title is-3 has-text-white">
            <span class="icon-text">
              <span class="icon has-text-danger">
                <i class="fas fa-star"></i>
              </span>
              <span>Por que escolher o CineRadar?</span>
            </span>
          </h2>
          
          <div class="columns is-multiline">
            <div class="column is-one-third">
              <div class="has-text-centered">
                <span class="icon is-large has-text-primary">
                  <i class="fas fa-mobile-alt fa-3x"></i>
                </span>
                <p class="title is-5 has-text-white mt-3">100% Responsivo</p>
                <p class="has-text-white-ter">Acesse de qualquer dispositivo: celular, tablet ou computador</p>
              </div>
            </div>

            <div class="column is-one-third">
              <div class="has-text-centered">
                <span class="icon is-large has-text-warning">
                  <i class="fas fa-search fa-3x"></i>
                </span>
                <p class="title is-5 has-text-white mt-3">Fácil de Usar</p>
                <p class="has-text-white-ter">Interface intuitiva para encontrar rapidamente o que você procura</p>
              </div>
            </div>

            <div class="column is-one-third">
              <div class="has-text-centered">
                <span class="icon is-large has-text-success">
                  <i class="fas fa-sync fa-3x"></i>
                </span>
                <p class="title is-5 has-text-white mt-3">Sempre Atualizado</p>
                <p class="has-text-white-ter">Informações atualizadas diariamente direto das melhores fontes</p>
              </div>
            </div>

            <div class="column is-one-third">
              <div class="has-text-centered">
                <span class="icon is-large has-text-info">
                  <i class="fas fa-play-circle fa-3x"></i>
                </span>
                <p class="title is-5 has-text-white mt-3">Trailers Integrados</p>
                <p class="has-text-white-ter">Assista aos trailers sem sair do site</p>
              </div>
            </div>

            <div class="column is-one-third">
              <div class="has-text-centered">
                <span class="icon is-large has-text-danger">
                  <i class="fas fa-images fa-3x"></i>
                </span>
                <p class="title is-5 has-text-white mt-3">Galeria de Fotos</p>
                <p class="has-text-white-ter">Veja imagens oficiais e bastidores dos filmes</p>
              </div>
            </div>

            <div class="column is-one-third">
              <div class="has-text-centered">
                <span class="icon is-large has-text-primary">
                  <i class="fas fa-moon fa-3x"></i>
                </span>
                <p class="title is-5 has-text-white mt-3">Design Moderno</p>
                <p class="has-text-white-ter">Interface elegante e confortável para os olhos</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Call to Action -->
        <div class="box mt-5" style="background-color: var(--background-card);">
          <h2 class="title is-3 has-text-white has-text-centered">
            <span class="icon-text">
              <span class="icon has-text-danger">
                <i class="fas fa-heart"></i>
              </span>
              <span>Feito para Amantes de Cinema</span>
            </span>
          </h2>
          <p class="has-text-centered has-text-white-ter is-size-5">
            O CineRadar foi criado por quem ama cinema, para quem ama cinema. 
            Nossa missão é proporcionar a melhor experiência de descoberta de filmes na web brasileira!
          </p>
          <div class="has-text-centered mt-5">
            <router-link to="/" class="button is-primary is-large">
              <span class="icon">
                <i class="fas fa-film"></i>
              </span>
              <span>Começar a Explorar</span>
            </router-link>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { onMounted } from 'vue'
import { useHead } from '../composables/useHead.js'

export default {
  name: 'About',
  setup() {
    // SEO
    useHead({
      title: 'Sobre - CineRadar',
      meta: [
        { name: 'description', content: 'Conheça o CineRadar, sua plataforma completa para descobrir filmes. Saiba mais sobre as tecnologias utilizadas e funcionalidades oferecidas.' },
        { property: 'og:title', content: 'Sobre o CineRadar' },
        { property: 'og:description', content: 'Plataforma moderna para descoberta de filmes com IA integrada' },
        { property: 'og:type', content: 'website' },
        { property: 'og:url', content: window.location.href },
        { name: 'twitter:card', content: 'summary' },
        { name: 'twitter:title', content: 'Sobre o CineRadar' },
        { name: 'twitter:description', content: 'Conheça nossa plataforma de filmes' }
      ],
      script: [
        {
          type: 'application/ld+json',
          innerHTML: JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'AboutPage',
            name: 'Sobre o CineRadar',
            description: 'Informações sobre o projeto CineRadar e suas tecnologias',
            url: window.location.href
          })
        }
      ]
    })

    return {}
  }
}
</script>

<style scoped>
.about-page {
  min-height: 100vh;
}

.card {
  height: 100%;
}
</style>