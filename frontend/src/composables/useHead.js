// Composable for managing document head (SEO)
export function useHead(options) {
  // Update title
  if (options.title) {
    document.title = options.title
  }

  // Update or create meta tags
  if (options.meta) {
    options.meta.forEach(metaTag => {
      const nameOrProperty = metaTag.name ? 'name' : 'property'
      const value = metaTag.name || metaTag.property
      let meta = document.querySelector(`meta[${nameOrProperty}="${value}"]`)
      
      if (!meta) {
        meta = document.createElement('meta')
        meta.setAttribute(nameOrProperty, value)
        document.head.appendChild(meta)
      }
      
      meta.setAttribute('content', metaTag.content)
    })
  }

  // Add JSON-LD scripts
  if (options.script) {
    options.script.forEach(script => {
      const scriptElement = document.createElement('script')
      scriptElement.type = script.type
      scriptElement.innerHTML = script.innerHTML
      
      // Remove existing JSON-LD script if exists
      const existingScript = document.querySelector('script[type="application/ld+json"]')
      if (existingScript) {
        existingScript.remove()
      }
      
      document.head.appendChild(scriptElement)
    })
  }
}
