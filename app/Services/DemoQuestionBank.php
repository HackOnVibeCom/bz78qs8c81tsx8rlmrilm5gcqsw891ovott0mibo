<?php

declare(strict_types=1);

namespace ChallengeChain\Services;

/**
 * Built-in demo question bank used when no AI API key is configured (DEMO MODE).
 * @phpstan-type Q array{0:string, 1:list<string>, 2:int, 3:string}
 */
final class DemoQuestionBank
{
    /** @var array<string, list<Q>> */
    private const BANK = [
        'general' => [
            ['Which planet is known as the Red Planet?', ['Mars', 'Venus', 'Jupiter', 'Saturn'], 0, 'Iron oxide dust gives Mars its reddish color.'],
            ['How many continents are there on Earth?', ['7', '5', '6', '8'], 0, 'Africa, Antarctica, Asia, Europe, North America, Oceania and South America.'],
            ['What is the largest ocean on Earth?', ['Pacific', 'Atlantic', 'Indian', 'Arctic'], 0, 'The Pacific covers about 63 million square km.'],
            ['Who painted the Mona Lisa?', ['Leonardo da Vinci', 'Vincent van Gogh', 'Pablo Picasso', 'Claude Monet'], 0, 'Leonardo da Vinci painted it in the early 16th century.'],
            ['What is the chemical symbol for gold?', ['Au', 'Ag', 'Fe', 'Go'], 0, 'Au comes from the Latin word aurum.'],
            ['Which is the longest river in the world?', ['Nile', 'Amazon', 'Yangtze', 'Mississippi'], 0, 'The Nile runs about 6,650 km through northeastern Africa.'],
            ['What language has the most native speakers?', ['Mandarin Chinese', 'English', 'Spanish', 'Hindi'], 0, 'Mandarin has around 920 million native speakers.'],
            ['How many days are in a leap year?', ['366', '365', '364', '367'], 0, 'February gains a 29th day every four years.'],
            ['What is the smallest prime number?', ['2', '1', '3', '5'], 0, '2 is the only even prime number.'],
            ['Which metal is liquid at room temperature?', ['Mercury', 'Lead', 'Zinc', 'Aluminium'], 0, 'Mercury melts at -39°C and boils at 357°C.'],
            ['What does the word "algorithm" originally refer to?', ['The name of a Persian mathematician', 'A Latin word for algebra', 'A Greek term for logic', 'An Arabic word for number'], 0, 'It comes from al-Khwarizmi, the 9th-century Persian scholar.'],
            ['How many degrees are in a circle?', ['360', '180', '90', '100'], 0, 'A full rotation is divided into 360 degrees.'],
        ],
        'technology' => [
            ['What does CPU stand for?', ['Central Processing Unit', 'Computer Personal Unit', 'Central Program Utility', 'Core Processing Unit'], 0, 'The CPU executes program instructions.'],
            ['Which company developed the iPhone?', ['Apple', 'Samsung', 'Google', 'Microsoft'], 0, 'Apple launched the first iPhone in 2007.'],
            ['What does HTML stand for?', ['HyperText Markup Language', 'High Tech Modern Language', 'Hyper Transfer Markup Language', 'Home Tool Markup Language'], 0, 'HTML structures content on the web.'],
            ['What year was Facebook founded?', ['2004', '2006', '2001', '2008'], 0, 'Mark Zuckerberg launched it from Harvard in 2004.'],
            ['Which language is known for its coffee mascot and runs on the JVM?', ['Java', 'Python', 'Ruby', 'C#'], 0, 'Java runs on the JVM and uses a coffee-cup logo.'],
            ['What does RAM stand for?', ['Random Access Memory', 'Readily Available Memory', 'Rapid Access Module', 'Run All Programs'], 0, 'RAM is volatile memory used while the computer runs.'],
            ['Which protocol secures web traffic with encryption?', ['HTTPS', 'FTP', 'SMTP', 'HTTP'], 0, 'HTTPS = HTTP wrapped in TLS encryption.'],
            ['Who is considered the first computer programmer?', ['Ada Lovelace', 'Alan Turing', 'Grace Hopper', 'Charles Babbage'], 0, 'Ada Lovelace wrote an algorithm for the Analytical Engine in 1843.'],
            ['What does SQL stand for?', ['Structured Query Language', 'Simple Query Logic', 'Structured Question Language', 'System Query Layer'], 0, 'SQL is the standard language for relational databases.'],
            ['Which company owns GitHub?', ['Microsoft', 'Google', 'Amazon', 'Meta'], 0, 'Microsoft acquired GitHub in 2018 for $7.5B.'],
            ['What is the brain of a computer called when it executes instructions?', ['Processor', 'Monitor', 'Keyboard', 'Hard drive'], 0, 'The processor (CPU) carries out instructions.'],
            ['In which decade was the World Wide Web invented?', ['1980s', '1990s', '1970s', '2000s'], 0, 'Tim Berners-Lee created the WWW at CERN in 1989.'],
        ],
        'science' => [
            ['What is the chemical symbol for water?', ['H2O', 'CO2', 'O2', 'NaCl'], 0, 'Two hydrogen atoms bonded to one oxygen atom.'],
            ['How many bones are in the adult human body?', ['206', '180', '220', '300'], 0, 'Babies are born with about 270 bones that fuse over time.'],
            ['What gas do plants absorb for photosynthesis?', ['Carbon dioxide', 'Oxygen', 'Nitrogen', 'Hydrogen'], 0, 'Plants take in CO2 and release oxygen.'],
            ['What is the speed of light in a vacuum (approx.)?', ['300,000 km/s', '150,000 km/s', '500,000 km/s', '1,000,000 km/s'], 0, 'Exactly 299,792,458 metres per second.'],
            ['Which part of the cell contains DNA?', ['Nucleus', 'Membrane', 'Ribosome', 'Cytoplasm'], 0, 'The nucleus stores the cell genetic material.'],
            ['What force keeps planets in orbit?', ['Gravity', 'Magnetism', 'Friction', 'Centrifugal force'], 0, 'Gravity between the sun and planets holds orbits.'],
            ['What is the hardest naturally occurring substance?', ['Diamond', 'Quartz', 'Iron', 'Graphite'], 0, 'Diamond scores 10 on the Mohs hardness scale.'],
            ['How many chambers does the human heart have?', ['4', '2', '3', '6'], 0, 'Two atria and two ventricles.'],
            ['What does DNA stand for?', ['Deoxyribonucleic acid', 'Dynamic nucleic acid', 'Deoxyribo nuclear acid', 'Dual nucleic acid'], 0, 'DNA carries genetic instructions in all living things.'],
            ['Which element has the atomic number 1?', ['Hydrogen', 'Helium', 'Oxygen', 'Carbon'], 0, 'Hydrogen has one proton.'],
            ['What is the powerhouse of the cell?', ['Mitochondria', 'Nucleus', 'Golgi body', 'Lysosome'], 0, 'Mitochondria produce ATP through cellular respiration.'],
            ['At what temperature does water boil at sea level?', ['100°C', '90°C', '110°C', '120°C'], 0, '100°C at standard atmospheric pressure.'],
        ],
        'history' => [
            ['In which year did World War II end?', ['1945', '1944', '1946', '1943'], 0, 'WWII ended in September 1945 after Japan surrendered.'],
            ['Who was the first President of the United States?', ['George Washington', 'Abraham Lincoln', 'Thomas Jefferson', 'John Adams'], 0, 'Washington took office in 1789.'],
            ['Which empire built Machu Picchu?', ['Inca', 'Aztec', 'Maya', 'Roman'], 0, 'The Inca built it in the 15th century in Peru.'],
            ['In which year did the Titanic sink?', ['1912', '1905', '1918', '1899'], 0, 'It struck an iceberg on April 15, 1912.'],
            ['Who wrote the Declaration of Independence?', ['Thomas Jefferson', 'Benjamin Franklin', 'John Adams', 'James Madison'], 0, 'Jefferson drafted it in June 1776.'],
            ['Which wall fell in 1989?', ['Berlin Wall', 'Great Wall', 'Western Wall', 'Hadrian Wall'], 0, 'The Berlin Wall fell on November 9, 1989.'],
            ['The pyramids of Giza were built for which civilization?', ['Ancient Egypt', 'Ancient Greece', 'Persia', 'Mesopotamia'], 0, 'They were tombs for pharaohs of the Fourth Dynasty.'],
            ['Who was the first person to walk on the Moon?', ['Neil Armstrong', 'Buzz Aldrin', 'Yuri Gagarin', 'John Glenn'], 0, 'Armstrong stepped onto the Moon on July 20, 1969.'],
            ['Which country gifted the Statue of Liberty to the USA?', ['France', 'Spain', 'Italy', 'England'], 0, 'France gifted it in 1886 as a symbol of friendship.'],
            ['In which century did the Renaissance begin?', ['14th century', '16th century', '12th century', '18th century'], 0, 'It started in Italy around the 1300s.'],
            ['Who was known as the Maid of Orléans?', ['Joan of Arc', 'Marie Antoinette', 'Catherine the Great', 'Elizabeth I'], 0, 'Joan of Arc led French troops during the Hundred Years War.'],
            ['Which ancient city was buried by Mount Vesuvius in 79 AD?', ['Pompeii', 'Athens', 'Sparta', 'Carthage'], 0, 'Volcanic ash preserved Pompeii remarkably well.'],
        ],
        'geography' => [
            ['What is the capital of France?', ['Paris', 'Lyon', 'Marseille', 'Nice'], 0, 'Paris has been the capital since the 10th century.'],
            ['Which is the largest country in the world by area?', ['Russia', 'Canada', 'China', 'United States'], 0, 'Russia spans over 17 million square km.'],
            ['What is the tallest mountain on Earth?', ['Mount Everest', 'K2', 'Kilimanjaro', 'Denali'], 0, 'Everest rises 8,849 metres above sea level.'],
            ['Which desert is the largest hot desert?', ['Sahara', 'Gobi', 'Kalahari', 'Arabian'], 0, 'The Sahara covers about 9.2 million square km.'],
            ['On which continent is Brazil located?', ['South America', 'North America', 'Africa', 'Europe'], 0, 'Brazil occupies most of South America.'],
            ['What is the smallest country in the world?', ['Vatican City', 'Monaco', 'San Marino', 'Liechtenstein'], 0, 'Vatican City is just 0.44 square km.'],
            ['Which river flows through London?', ['Thames', 'Seine', 'Rhine', 'Danube'], 0, 'The Thames runs through central London.'],
            ['What is the capital of Japan?', ['Tokyo', 'Kyoto', 'Osaka', 'Nagoya'], 0, 'Tokyo became the imperial capital in 1868.'],
            ['Which line divides Earth into Northern and Southern hemispheres?', ['Equator', 'Prime Meridian', 'Tropic of Cancer', 'Arctic Circle'], 0, 'The Equator sits at 0 degrees latitude.'],
            ['Which country has the most time zones?', ['France', 'Russia', 'United States', 'China'], 0, 'France has 12 time zones thanks to overseas territories.'],
            ['What is the largest island in the world?', ['Greenland', 'New Guinea', 'Borneo', 'Madagascar'], 0, 'Greenland covers about 2.16 million square km.'],
            ['Which sea is saltiest?', ['Dead Sea', 'Red Sea', 'Black Sea', 'Caspian Sea'], 0, 'The Dead Sea is about 9.6 times saltier than the ocean.'],
        ],
        'sports' => [
            ['How many players are on a soccer team on the field?', ['11', '9', '10', '12'], 0, 'Each team fields 11 players including the goalkeeper.'],
            ['Which country hosted the 2016 Summer Olympics?', ['Brazil', 'China', 'United Kingdom', 'Japan'], 0, 'Rio de Janeiro hosted the Games in 2016.'],
            ['How many points is a free throw worth in basketball?', ['1', '2', '3', '0'], 0, 'Free throws are worth one point each.'],
            ['In which sport would you perform a slam dunk?', ['Basketball', 'Volleyball', 'Tennis', 'Baseball'], 0, 'A slam dunk scores two points in basketball.'],
            ['How long is an Olympic swimming pool?', ['50 meters', '25 meters', '100 meters', '40 meters'], 0, 'Olympic pools are 50 metres long.'],
            ['Which country has won the most FIFA World Cups?', ['Brazil', 'Germany', 'Italy', 'Argentina'], 0, 'Brazil has won the trophy five times.'],
            ['How many rings are on the Olympic flag?', ['5', '4', '6', '7'], 0, 'Five interlocking rings represent the five continents.'],
            ['What is the maximum score possible in a single bowling frame?', ['30', '20', '10', '25'], 0, 'A strike plus two more strikes in the next frames.'],
            ['Which sport is played at Wimbledon?', ['Tennis', 'Cricket', 'Golf', 'Rugby'], 0, 'Wimbledon is the oldest Grand Slam tennis tournament.'],
            ['How many periods are in an ice hockey game?', ['3', '4', '2', '5'], 0, 'NHL games consist of three 20-minute periods.'],
            ['Who holds the men world record for the 100m sprint?', ['Usain Bolt', 'Carl Lewis', 'Jesse Owens', 'Tyson Gay'], 0, 'Bolt ran 9.58 seconds in Berlin in 2009.'],
            ['In baseball, how many strikes make an out?', ['3', '2', '4', '5'], 0, 'Three strikes and the batter is out.'],
        ],
        'movies' => [
            ['Who directed Jurassic Park?', ['Steven Spielberg', 'James Cameron', 'Ridley Scott', 'George Lucas'], 0, 'Spielberg directed the 1993 classic.'],
            ['Which film features the line "I am your father"?', ['The Empire Strikes Back', 'A New Hope', 'Return of the Jedi', 'The Force Awakens'], 0, 'Darth Vader reveals it in Episode V.'],
            ['How many Oscars did Titanic win in 1998?', ['11', '8', '14', '9'], 0, 'It tied the record with 11 Academy Awards.'],
            ['What is the highest-grossing film of all time?', ['Avatar', 'Avengers: Endgame', 'Titanic', 'Star Wars: The Force Awakens'], 0, 'Avatar leads with over $2.9 billion worldwide.'],
            ['Which actor plays Iron Man in the MCU?', ['Robert Downey Jr.', 'Chris Evans', 'Chris Hemsworth', 'Mark Ruffalo'], 0, 'RDJ defined the role from 2008 onward.'],
            ['In Finding Nemo, what type of fish is Nemo?', ['Clownfish', 'Goldfish', 'Angelfish', 'Guppy'], 0, 'Clownfish live in sea anemones.'],
            ['Who played Jack in Titanic?', ['Leonardo DiCaprio', 'Brad Pitt', 'Matt Damon', 'Johnny Depp'], 0, 'DiCaprio starred opposite Kate Winslet.'],
            ['Which movie franchise features a shark named Bruce?', ['Jaws', 'Finding Nemo', 'The Meg', 'Deep Blue Sea'], 0, 'The mechanical shark was nicknamed Bruce by the crew.'],
            ['What year was the first Toy Story released?', ['1995', '1997', '1993', '2000'], 0, 'Toy Story premiered in November 1995.'],
            ['Who directed The Dark Knight?', ['Christopher Nolan', 'Zack Snyder', 'Tim Burton', 'Joel Schumacher'], 0, 'Nolan directed the 2008 Batman film.'],
            ['Which film won Best Picture at the 2020 Oscars?', ['Parasite', '1917', 'Joker', 'Once Upon a Time in Hollywood'], 0, 'Parasite became the first non-English-language winner.'],
            ['What is the name of the fictional African country in Black Panther?', ['Wakanda', 'Zamunda', 'Genovia', 'Latveria'], 0, 'Wakanda is home to Black Panther.'],
        ],
        'music' => [
            ['How many keys does a standard piano have?', ['88', '76', '61', '96'], 0, '88 keys spanning 7 octaves plus a minor third.'],
            ['Which band released the album Abbey Road?', ['The Beatles', 'The Rolling Stones', 'Queen', 'Pink Floyd'], 0, 'Released in September 1969.'],
            ['How many members are in a standard string quartet?', ['4', '3', '5', '6'], 0, 'Two violins, one viola and one cello.'],
            ['Who is known as the King of Pop?', ['Michael Jackson', 'Elvis Presley', 'Prince', 'Freddie Mercury'], 0, 'Jackson earned the nickname in the 1980s.'],
            ['Which instrument has 6 strings by default?', ['Guitar', 'Violin', 'Harp', 'Ukulele'], 0, 'Standard guitars have six strings tuned EADGBE.'],
            ['In music, how many beats are in a waltz measure?', ['3', '4', '2', '6'], 0, 'Waltzes are written in 3/4 time.'],
            ['Which composer wrote the 9th Symphony with the Ode to Joy?', ['Beethoven', 'Mozart', 'Bach', 'Chopin'], 0, 'Beethoven completed it in 1824.'],
            ['What does BPM measure in music?', ['Tempo', 'Volume', 'Pitch', 'Key'], 0, 'Beats per minute indicates the speed of a track.'],
            ['Which artist released the album 1989?', ['Taylor Swift', 'Adele', 'Beyoncé', 'Lady Gaga'], 0, 'Swift released it in 2014 as her pop transition.'],
            ['How many semitones are in an octave?', ['12', '7', '8', '24'], 0, 'The chromatic scale has 12 semitones.'],
            ['Which instrument is Yo-Yo Ma famous for?', ['Cello', 'Violin', 'Piano', 'Flute'], 0, 'Ma is one of the most celebrated cellists alive.'],
            ['Which genre did Jazz evolve from in New Orleans?', ['Blues and ragtime', 'Classical', 'Country', 'Electronic'], 0, 'Jazz blended blues, ragtime and brass band traditions.'],
        ],
    ];

    public const CATEGORIES = [
        'general' => 'General Knowledge',
        'technology' => 'Technology',
        'science' => 'Science',
        'history' => 'History',
        'geography' => 'Geography',
        'sports' => 'Sports',
        'movies' => 'Movies & TV',
        'music' => 'Music',
    ];

    /**
     * @return array{title:string, description:string, category:string, topic:string, difficulty:string, language:string, questions:list<array{question:string, options:list<string>, correct_index:int, explanation:string}>, demo:bool}
     */
    public static function generate(string $topic, string $category, string $difficulty, int $count, string $language): array
    {
        $category = self::CATEGORIES[$category] ?? null ? $category : 'general';
        $pool = self::BANK[$category] ?? self::BANK['general'];
        $count = max(3, min($count, count($pool)));

        $picked = $pool;
        shuffle($picked);
        $picked = array_slice($picked, 0, $count);

        $questions = [];
        foreach ($picked as [$question, $options, $correctIndex, $explanation]) {
            $indexed = array_values($options);
            shuffle($indexed);
            $correctText = $options[$correctIndex];
            $newIndex = array_search($correctText, $indexed, true);
            $questions[] = [
                'question' => $question,
                'options' => $indexed,
                'correct_index' => $newIndex === false ? 0 : (int) $newIndex,
                'explanation' => $explanation,
            ];
        }

        $topicLabel = trim($topic) !== '' ? trim($topic) : (self::CATEGORIES[$category] ?? 'General');
        $difficultyLabel = ucfirst($difficulty);

        return [
            'title' => $topicLabel . ' — ' . $difficultyLabel . ' Challenge',
            'description' => 'Test your knowledge of ' . $topicLabel . ' with ' . count($questions) . ' questions. Beat the clock and top the leaderboard!',
            'category' => $category,
            'topic' => $topicLabel,
            'difficulty' => in_array($difficulty, ['easy', 'medium', 'hard'], true) ? $difficulty : 'medium',
            'language' => $language,
            'questions' => $questions,
            'demo' => true,
        ];
    }

    /** @return list<string> */
    public static function categories(): array
    {
        return array_keys(self::CATEGORIES);
    }

    /** @return list<Q> */
    public static function forCategory(string $category): array
    {
        return self::BANK[$category] ?? self::BANK['general'];
    }
}
