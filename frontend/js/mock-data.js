const demoChallenge = {
  id: 1,
  title: 'Most visited cities in the world',
  description: 'Rank these cities from the most visited (1) to the least visited (5).',
  date: '2026-10-23',
  items: [
    { id: 1, text: 'Bangkok', detail: 'Thailand', art: 'bangkok', correctPosition: 1 },
    { id: 2, text: 'Paris', detail: 'France', art: 'paris', correctPosition: 2 },
    { id: 3, text: 'London', detail: 'United Kingdom', art: 'london', correctPosition: 3 },
    { id: 4, text: 'Dubai', detail: 'United Arab Emirates', art: 'dubai', correctPosition: 4 },
    { id: 5, text: 'Singapore', detail: 'Singapore', art: 'singapore', correctPosition: 5 }
  ]
};

const leaderboardData = {
  today: [{ name: 'almina', points: 100 }, { name: 'TarikB', points: 100 }, { name: 'leon', points: 60 }, { name: 'Timur', points: 60 }, { name: 'dino', points: 40 }, { name: 'sara', points: 40 }, { name: 'amir', points: 20 }, { name: 'ena', points: 20 }],
  week: [{ name: 'TarikB', points: 540 }, { name: 'almina', points: 520 }, { name: 'sara', points: 480 }, { name: 'Timur', points: 460 }, { name: 'leon', points: 420 }, { name: 'dino', points: 400 }, { name: 'ena', points: 380 }, { name: 'amir', points: 340 }],
  all: [{ name: 'almina', points: 1640 }, { name: 'TarikB', points: 1520 }, { name: 'leon', points: 1380 }, { name: 'Timur', points: 1240 }, { name: 'sara', points: 1180 }, { name: 'ena', points: 1060 }, { name: 'dino', points: 980 }, { name: 'amir', points: 860 }]
};

const historyData = [
  { date: 'Oct 22, 2026', title: 'Most visited cities', score: 60, icon: '🌍' },
  { date: 'Oct 21, 2026', title: 'Largest countries', score: 100, icon: '🗺' },
  { date: 'Oct 20, 2026', title: 'Highest grossing movies', score: 40, icon: '🎬' },
  { date: 'Oct 19, 2026', title: 'Fastest land animals', score: 100, icon: '🐾' },
  { date: 'Oct 18, 2026', title: 'Tallest buildings', score: 20, icon: '🏙' }
];

const adminData = {
  challenges: [
    { id: 1, title: demoChallenge.title, description: demoChallenge.description, challenge_date: '2026-10-23', status: 'Published', items: ['Bangkok', 'Paris', 'London', 'Dubai', 'Singapore'] },
    { id: 2, title: 'Largest countries', description: 'Rank by total area, largest first.', challenge_date: '2026-10-24', status: 'Pending', items: ['Russia', 'Canada', 'China', 'United States', 'Brazil'] },
    { id: 3, title: 'Highest grossing movies', description: 'Rank by worldwide box office, highest first.', challenge_date: '2026-10-25', status: 'Draft', items: ['Avatar', 'Avengers: Endgame', 'Avatar: The Way of Water', 'Titanic', 'Star Wars: The Force Awakens'] },
    { id: 4, title: 'Fastest land animals', description: 'Rank from fastest to slowest.', challenge_date: '2026-10-26', status: 'Draft', items: ['Cheetah', 'Pronghorn', 'Springbok', 'Wildebeest', 'Lion'] }
  ],
  users: [
    { id: 1, name: 'Timur', email: 'timur@example.com', role: 'user' },
    { id: 2, name: 'Almina', email: 'almina@example.com', role: 'user' },
    { id: 3, name: 'Admin', email: 'admin@example.com', role: 'admin' }
  ],
  attempts: [
    { id: 1, user_id: 1, challenge_id: 1, score: 60, completed_at: '2026-10-23T10:15' },
    { id: 2, user_id: 2, challenge_id: 1, score: 100, completed_at: '2026-10-23T10:22' }
  ],
  'attempt-items': [
    { id: 1, attempt_id: 1, challenge_item_id: 1, selected_position: 1 },
    { id: 2, attempt_id: 1, challenge_item_id: 2, selected_position: 2 },
    { id: 3, attempt_id: 1, challenge_item_id: 3, selected_position: 4 },
    { id: 4, attempt_id: 1, challenge_item_id: 4, selected_position: 3 },
    { id: 5, attempt_id: 1, challenge_item_id: 5, selected_position: 5 },
    { id: 6, attempt_id: 2, challenge_item_id: 1, selected_position: 1 },
    { id: 7, attempt_id: 2, challenge_item_id: 2, selected_position: 2 },
    { id: 8, attempt_id: 2, challenge_item_id: 3, selected_position: 3 },
    { id: 9, attempt_id: 2, challenge_item_id: 4, selected_position: 4 },
    { id: 10, attempt_id: 2, challenge_item_id: 5, selected_position: 5 }
  ]
};
