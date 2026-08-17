export default defineEventHandler((event) => {
  const quotes = [
    {
      text: "Kitob — bilim manbai, ko‘ngil chirog‘idir.",
      author: "Alisher Navoiy"
    },
    {
      text: "Bilim — qalbni yorituvchi abadiy nurdir.",
      author: "Ibn Sino"
    },
    {
      text: "Bilim — insonni komillikka yetaklovchi eng ulug‘ kuchdir.",
      author: "Abu Rayhon Beruniy"
    },
    {
      text: "Kitob o‘qish — aql va tafakkurni charxlaydigan eng buyuk ne’matdir.",
      author: "Abdulla Qodiriy"
    },
    {
      text: "Har bir o‘qilgan sahifa — bilim va hikmat xazinasidir.",
      author: "Zahiriddin Muhammad Bobur"
    },
    {
      text: "Kitob — sukutdagi eng sofdil va eng sadoqatli do‘stdir.",
      author: "Jaloliddin Rumiy"
    },
    {
      text: "Kitob bilan do‘st bo‘lgan inson hech qachon yolg‘iz qolmaydi.",
      author: "Cho‘lpon"
    },
    {
      text: "Yaxshi kitob — inson ruhini yuksaltiruvchi buyuk kuchdir.",
      author: "Lev Tolstoy"
    }
  ];

  // Pick a random quote on each request
  const quote = quotes[Math.floor(Math.random() * quotes.length)];

  // Determine font size based on text length
  const fontSize = quote.text.length > 50 ? 40 : 48;

  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 630" width="1200" height="630">
  <rect width="1200" height="630" fill="#FFFFFF"/>
  <g transform="translate(600, 290)" text-anchor="middle">
    <text x="0" y="-70" font-family="'Georgia', 'Times New Roman', serif" font-size="72" font-style="italic" fill="#94A3B8" opacity="0.45">“</text>
    <text x="0" y="0" font-family="'Georgia', 'Times New Roman', serif" font-size="${fontSize}" font-weight="500" fill="#0F172A" letter-spacing="0.5">
      ${quote.text}
    </text>
    <text x="0" y="80" font-family="'Georgia', 'Times New Roman', serif" font-size="26" font-style="italic" fill="#64748B">
      — ${quote.author}
    </text>
  </g>
</svg>`;

  setHeader(event, 'Content-Type', 'image/svg+xml');
  setHeader(event, 'Cache-Control', 'no-cache, no-store, must-revalidate');

  return svg;
});
