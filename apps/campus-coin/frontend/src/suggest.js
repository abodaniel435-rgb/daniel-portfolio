const KEYWORDS = [
  ['food', ['food', 'cafe', 'canteen', 'lunch', 'dinner', 'breakfast', 'jollof', 'snack', 'pizza', 'delivery', 'chop', 'restaurant']],
  ['transport', ['bus', 'uber', 'bolt', 'taxi', 'fuel', 'transport', 'keke', 'train', 'fare', 'ride']],
  ['hostel', ['hostel', 'rent', 'landlord', 'lodge', 'room', 'accommodation']],
  ['academic', ['book', 'tuition', 'stationery', 'print', 'course', 'pen', 'notebook', 'handout']],
  ['subscription', ['netflix', 'spotify', 'subscription', 'data plan', 'airtime', 'youtube', 'streaming']],
  ['entertainment', ['movie', 'cinema', 'outing', 'party', 'game', 'concert', 'show']],
  ['allowance', ['allowance', 'family', 'parents', 'upkeep']],
  ['part-time', ['salary', 'wage', 'job', 'gig', 'shift', 'payroll']],
  ['scholarship', ['scholarship', 'grant', 'bursary']],
  ['gift', ['gift', 'birthday', 'dash']],
]

const MEMORY = 'cc-category-memory'

function readMemory() {
  try {
    return JSON.parse(localStorage.getItem(MEMORY) || '{}')
  } catch {
    return {}
  }
}

export function rememberCategory(type, description, categoryId) {
  const text = description.trim().toLowerCase()
  if (!text || !categoryId) return
  const memory = readMemory()
  memory[`${type}:${text}`] = String(categoryId)
  localStorage.setItem(MEMORY, JSON.stringify(memory))
}

export function suggestCategory(description, categories, type) {
  const text = description.trim().toLowerCase()
  if (!text) return null
  const pool = categories.filter((category) => category.type === type)
  const remembered = readMemory()[`${type}:${text}`]
  const rememberedMatch = pool.find((category) => String(category.id) === String(remembered))
  if (rememberedMatch) return rememberedMatch
  for (const category of pool) {
    const name = category.name.toLowerCase()
    if (text.includes(name)) return category
    const rule = KEYWORDS.find(([label]) => name.includes(label))
    if (rule && rule[1].some((word) => text.includes(word))) return category
  }
  return null
}
