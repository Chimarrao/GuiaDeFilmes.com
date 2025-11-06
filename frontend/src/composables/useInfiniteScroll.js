import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'

export function useInfiniteScroll(loadMoreCallback) {
  const observer = ref(null)
  const loadMoreTrigger = ref(null)

  const observerCallback = (entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        loadMoreCallback()
      }
    })
  }

  const setupObserver = async () => {
    // Wait for DOM to be ready
    await nextTick()
    
    // Disconnect existing observer
    if (observer.value) {
      observer.value.disconnect()
    }

    // Create new observer
    observer.value = new IntersectionObserver(observerCallback, {
      root: null,
      rootMargin: '200px',
      threshold: 0.1
    })

    // Observe the trigger element if it exists
    if (loadMoreTrigger.value) {
      observer.value.observe(loadMoreTrigger.value)
    }
  }

  const disconnectObserver = () => {
    if (observer.value) {
      observer.value.disconnect()
      observer.value = null
    }
  }

  // Watch for changes in loadMoreTrigger
  watch(loadMoreTrigger, (newVal) => {
    if (newVal) {
      setupObserver()
    }
  })

  onMounted(() => {
    setupObserver()
  })

  onUnmounted(() => {
    disconnectObserver()
  })

  return {
    loadMoreTrigger,
    setupObserver,
    disconnectObserver
  }
}
