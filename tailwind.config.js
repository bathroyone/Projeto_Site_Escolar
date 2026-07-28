module.exports = {
  content: [
    "./**/*.html",
    "./**/*.js",
    "./**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        azul: {
          principal: '#0a2463',
          escuro: '#051435',
          claro: '#1e3a8a'
        },
        amarelo: {
          destaque: '#ffd700',
          claro: '#ffed4a'
        },
        verde: {
          complementar: '#2d6a4f',
          claro: '#40916c'
        },
        preto: {
          principal: '#000000',
          escuro: '#1a1a1a'
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        display: ['Poppins', 'system-ui', 'sans-serif']
      },
      borderRadius: {
        '2xl': '1rem',
        '3xl': '1.5rem'
      },
      backdropBlur: {
        xs: '2px'
      }
    }
  },
  plugins: []
}
