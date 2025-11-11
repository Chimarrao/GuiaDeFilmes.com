<template>
  <div id="app">
    <Header />
    <main>
      <router-view v-slot="{ Component }">
        <keep-alive :include="['Released', 'Upcoming', 'InTheaters', 'GenreMovies', 'CountryMovies', 'YearMovies']">
          <component :is="Component" />
        </keep-alive>
      </router-view>
    </main>
    <Footer />
  </div>
</template>

<script>
import Header from './components/Header.vue'
import Footer from './components/Footer.vue'

export default {
  name: 'App',
  components: {
    Header,
    Footer
  }
}
</script>

<style>
#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

main {
  flex: 1;
}
</style>