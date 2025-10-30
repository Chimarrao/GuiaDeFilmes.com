<template>
  <footer class="footer">
    <div class="container">
      <div class="columns">
        <div class="column is-4">
          <h3 class="title is-5 has-text-white">
            <span class="icon-text">
              <span class="icon has-text-danger">
                <i class="fas fa-film"></i>
              </span>
              <span>CineRadar</span>
            </span>
          </h3>
          <p class="has-text-grey-light">
            Seu radar cinematográfico. Descubra, explore e acompanhe os melhores filmes.
          </p>
        </div>

        <div class="column is-4">
          <h4 class="title is-6 has-text-white">Navegação</h4>
          <ul>
            <li><router-link to="/" class="has-text-grey-light">Home</router-link></li>
            <li><router-link to="/estreias" class="has-text-grey-light">Próximas Estreias</router-link></li>
            <li><router-link to="/em-cartaz" class="has-text-grey-light">Em Cartaz</router-link></li>
            <li><router-link to="/lancamentos" class="has-text-grey-light">Lançamentos</router-link></li>
            <li><router-link to="/sobre" class="has-text-grey-light">Sobre</router-link></li>
          </ul>
        </div>

        <div class="column is-4">
          <h4 class="title is-6 has-text-white">Redes Sociais</h4>
          <div class="social-icons">
            <a href="#" class="has-text-grey-light" aria-label="Facebook">
              <span class="icon is-large">
                <i class="fab fa-facebook fa-2x"></i>
              </span>
            </a>
            <a href="#" class="has-text-grey-light" aria-label="Twitter">
              <span class="icon is-large">
                <i class="fab fa-twitter fa-2x"></i>
              </span>
            </a>
            <a href="#" class="has-text-grey-light" aria-label="Instagram">
              <span class="icon is-large">
                <i class="fab fa-instagram fa-2x"></i>
              </span>
            </a>
          </div>
        </div>
      </div>

      <hr class="has-background-grey-dark">

      <div class="content has-text-centered has-text-grey-light">
        <p>
          <strong class="has-text-white">&copy; 2025 CineRadar</strong> - Todos os direitos reservados.
          <br>
          Dados fornecidos por <a href="https://www.themoviedb.org/" target="_blank" rel="noopener" class="has-text-danger">TMDB API</a>
        </p>
      </div>
    </div>
  </footer>
</template>

<script>
export default {
  name: 'Footer'
}
</script>

<style scoped>
.footer {
  background-color: #141414;
  padding: 3rem 1.5rem 2rem;
  border-top: 1px solid #333;
  margin-top: 4rem;
}

.footer ul {
  list-style: none;
  margin: 0;
  padding: 0;
}

.footer ul li {
  margin-bottom: 0.5rem;
}

.footer a:hover {
  color: #e50914 !important;
  text-decoration: none;
}

.social-icons {
  display: flex;
  gap: 1rem;
}

.social-icons a:hover {
  transform: translateY(-3px);
  transition: transform 0.3s ease;
}
</style>