<?php
/**
 * Demo Content Applicator — CLI only, idempotent.
 *
 * Usage: php tools/apply-demo-content.php
 *
 * Transforms the wellness site into a neutral portfolio demo covering
 * everyday wellness (nutrition, sleep, fitness, stress).
 *
 * @package Wellness_Demo
 */

if ( 'cli' !== php_sapi_name() ) {
	die( 'CLI only.' . PHP_EOL );
}

// Set working dir so wp-load resolves correctly.
chdir( dirname( __DIR__ ) );
require_once dirname( __DIR__ ) . '/wp-load.php';

$counts = array(
	'posts'       => 0,
	'pages'       => 0,
	'types'       => 0,
	'consultants' => 0,
);

// =========================================================================
// 1. SWITCHES ON
// =========================================================================
update_option( 'wc_consultations_enabled', 1 );
update_option( 'wc_questions_enabled', 1 );

// Publish consultation pages.
$publish_slugs = array( 'book-consultation', 'track-my-request', 'refund-policy', 'ask-a-question' );
foreach ( $publish_slugs as $slug ) {
	$page = get_page_by_path( $slug );
	if ( $page && 'publish' !== $page->post_status ) {
		wp_update_post( array( 'ID' => $page->ID, 'post_status' => 'publish' ) );
		echo "Published page: $slug\n";
	}
}

// =========================================================================
// 2. CATEGORIES — rename in-place, keeping IDs.
// =========================================================================
$cat_map = array(
	'intimacy'      => array(
		'name' => 'Nutrition & Eating',
		'slug' => 'nutrition-eating',
		'desc' => 'Evidence-informed articles on building healthy eating habits and understanding nutrition basics.',
	),
	'lifestyle'     => array(
		'name' => 'Sleep & Recovery',
		'slug' => 'sleep-recovery',
		'desc' => 'Practical guidance on improving sleep quality and supporting everyday physical recovery.',
	),
	'mens-health'   => array(
		'name' => 'Fitness & Movement',
		'slug' => 'fitness-movement',
		'desc' => 'Accessible fitness guidance for all activity levels, from beginners to regular movers.',
	),
	'womens-health' => array(
		'name' => 'Stress & Mind',
		'slug' => 'stress-mind',
		'desc' => 'Tools and everyday habits for managing stress and supporting mental wellbeing.',
	),
	'myths-vs-facts' => array(
		'name' => 'Myths vs Facts',
		'slug' => 'myths-vs-facts',
		'desc' => 'Separating common wellness myths from the evidence so you can make informed choices.',
	),
);

$cat_id_map = array(); // old_slug => new term_id

foreach ( $cat_map as $old_slug => $data ) {
	$term = get_term_by( 'slug', $old_slug, 'category' );
	if ( ! $term ) {
		// Try new slug in case already updated.
		$term = get_term_by( 'slug', $data['slug'], 'category' );
	}
	if ( $term ) {
		wp_update_term( $term->term_id, 'category', array(
			'name'        => $data['name'],
			'slug'        => $data['slug'],
			'description' => $data['desc'],
		) );
		$cat_id_map[ $old_slug ]       = $term->term_id;
		$cat_id_map[ $data['slug'] ]   = $term->term_id;
		echo "Updated category: {$data['name']}\n";
	} else {
		echo "WARNING: Category '$old_slug' not found.\n";
	}
}
delete_option( 'rewrite_rules' ); // Flush rewrites.

// Helper: get term_id by new slug.
function demo_cat_id( $new_slug ) {
	$t = get_term_by( 'slug', $new_slug, 'category' );
	return $t ? $t->term_id : 0;
}

// =========================================================================
// 3. POSTS — rewrite existing posts (idempotent via _demo_rewritten meta).
// =========================================================================
$articles = array(
	array(
		'title'   => 'Building a Balanced Plate: Simple Rules That Work',
		'slug'    => 'building-a-balanced-plate',
		'cat'     => 'nutrition-eating',
		'excerpt' => 'Simple, practical guidance on building meals that support your energy and long-term health.',
		'content' => <<<HTML
<h2>What Does a Balanced Plate Look Like?</h2>
<p>Building a balanced plate is one of the most practical things you can do to support your everyday health. Rather than following complex rules, the idea is to include a variety of foods from different groups so that your body gets what it needs across the day.</p>
<p>A commonly used guide is to fill roughly half your plate with vegetables and fruit, a quarter with whole grains or starchy foods, and the remaining quarter with a source of protein such as legumes, eggs, fish, or lean meat.</p>

<h2>Why Food Variety Matters</h2>
<p>Different foods provide different nutrients. Vegetables supply fibre and vitamins; whole grains provide sustained energy; protein supports muscle repair and keeps you fuller for longer. No single food covers all your nutritional needs, which is why variety over the week is more important than perfecting every individual meal.</p>
<p>Colour is a useful shortcut: a plate with several different colours is likely to contain a broader range of nutrients than one that is mostly beige or brown.</p>

<h2>Practical Tips for Everyday Meals</h2>
<p>You do not need to overhaul your diet overnight. Small, consistent changes tend to stick better than large ones. Some approaches that people find helpful include:</p>
<ul>
<li>Adding an extra serving of vegetables to meals you already enjoy.</li>
<li>Swapping refined grains (white bread, white rice) for whole-grain versions a few times a week.</li>
<li>Keeping fruit or nuts visible and within easy reach as a default snack.</li>
<li>Cooking in batches so that a healthier option is ready when time is short.</li>
</ul>

<h2>Drinks Count Too</h2>
<p>Water is the best all-round drink. Many people underestimate how much of their daily calorie and sugar intake comes from beverages such as sodas, sweetened coffees, or juices. Replacing one or two of these per day with water or unsweetened alternatives is a straightforward change with a noticeable impact over time.</p>

<h2>When to Seek Personalised Guidance</h2>
<p>General advice is a starting point, not a one-size-fits-all prescription. If you have a specific health condition, are pregnant, or have a history of disordered eating, it is worth speaking to a registered dietitian or your doctor for guidance tailored to your situation.</p>

<h2>Sources</h2>
<ul>
<li><a href="https://www.who.int" target="_blank" rel="noopener">World Health Organization (WHO)</a></li>
<li><a href="https://www.nhs.uk" target="_blank" rel="noopener">NHS (UK)</a></li>
<li><a href="https://www.cdc.gov" target="_blank" rel="noopener">Centers for Disease Control and Prevention (CDC)</a></li>
</ul>
HTML,
	),
	array(
		'title'   => 'Why Sleep Quality Matters More Than Hours',
		'slug'    => 'why-sleep-quality-matters',
		'cat'     => 'sleep-recovery',
		'excerpt' => 'Getting the right amount of sleep is important, but how well you sleep may matter even more.',
		'content' => <<<HTML
<h2>Hours vs Quality</h2>
<p>Most adults know they should aim for around seven to nine hours of sleep each night. But the number of hours alone does not tell the whole story. Someone who sleeps for eight hours but wakes repeatedly, or spends long periods in lighter stages of sleep, may feel less rested than someone who sleeps for six and a half hours of deep, uninterrupted sleep.</p>

<h2>What Happens During Good Sleep</h2>
<p>During a full night of sleep the body cycles through different stages, including light sleep, deep sleep, and REM (rapid eye movement) sleep. Deep sleep is when the body carries out most of its physical repair: muscles recover, the immune system is strengthened, and cells are renewed. REM sleep is linked to memory consolidation and mood regulation.</p>
<p>When sleep is fragmented or shortened, you get less time in these restorative stages.</p>

<h2>Common Factors That Affect Sleep Quality</h2>
<p>Many everyday habits have a direct effect on how well you sleep:</p>
<ul>
<li><strong>Screen time before bed.</strong> Blue light from phones and tablets suppresses melatonin, the hormone that signals to your brain that it is time to sleep.</li>
<li><strong>Caffeine timing.</strong> Caffeine has a half-life of around five to six hours, meaning a coffee at 4pm can still be affecting your alertness at 10pm.</li>
<li><strong>Room temperature.</strong> A slightly cooler bedroom (around 16–18°C / 60–65°F) is associated with better sleep for most people.</li>
<li><strong>Consistency.</strong> Going to bed and waking at roughly the same time every day, including weekends, helps to reinforce your body's natural sleep-wake rhythm.</li>
</ul>

<h2>Signs That Sleep Quality May Be Poor</h2>
<p>Beyond feeling tired in the morning, poor sleep quality can show up as difficulty concentrating, irritability, increased appetite (particularly for high-calorie foods), and reduced ability to handle everyday stressors. Over long periods, consistently poor sleep is linked to a range of health concerns.</p>

<h2>Simple Steps to Try Tonight</h2>
<p>Improving sleep quality does not require a complete lifestyle change. Setting your phone to night mode an hour before bed, keeping a consistent wake-up time, and making your bedroom as dark and quiet as possible are small steps that many people find make a noticeable difference within a week or two.</p>

<h2>Sources</h2>
<ul>
<li><a href="https://www.who.int" target="_blank" rel="noopener">World Health Organization (WHO)</a></li>
<li><a href="https://www.nhs.uk" target="_blank" rel="noopener">NHS (UK)</a></li>
<li><a href="https://www.cdc.gov" target="_blank" rel="noopener">Centers for Disease Control and Prevention (CDC)</a></li>
</ul>
HTML,
	),
	array(
		'title'   => 'How Much Exercise Do Adults Really Need?',
		'slug'    => 'how-much-exercise-do-adults-need',
		'cat'     => 'fitness-movement',
		'excerpt' => 'Current guidance on physical activity and how to fit movement into a busy schedule.',
		'content' => <<<HTML
<h2>The Recommended Amount</h2>
<p>Major public health organisations recommend that adults aim for at least 150 minutes of moderate-intensity activity per week, or 75 minutes of vigorous-intensity activity. That works out to around 30 minutes of moderate movement on five days per week — a target that is achievable for most people even with a full work schedule.</p>
<p>Muscle-strengthening activities (such as bodyweight exercises, resistance bands, or weights) are also recommended on two or more days per week.</p>

<h2>What Counts as Moderate Activity?</h2>
<p>Moderate-intensity activity raises your heart rate and makes you breathe a little harder, but you can still hold a conversation. Examples include brisk walking, cycling on flat ground, swimming at a relaxed pace, dancing, and most gardening tasks.</p>
<p>Vigorous activity — running, fast cycling, aerobics — requires more effort and you can only say a few words without pausing to breathe.</p>

<h2>Does It Have to Be Done All at Once?</h2>
<p>No. Research consistently shows that breaking your activity into shorter sessions throughout the day provides similar health benefits to one longer session. Three ten-minute walks count the same as one thirty-minute walk. This is useful to know if finding a continuous block of time is a challenge.</p>

<h2>Starting From Zero</h2>
<p>If you are currently inactive, the most important step is simply to start moving more than you do now. Even small increases in daily movement — taking the stairs, walking to a further bus stop, standing up every hour — reduce health risks compared to being completely sedentary. Build gradually rather than trying to meet the full recommendation from day one.</p>

<h2>Listening to Your Body</h2>
<p>Some muscle soreness after unfamiliar exercise is normal. Sharp pain, joint pain, or chest discomfort is not normal and is a signal to stop and seek medical advice before continuing. If you have any existing health conditions, check with your doctor before starting a new exercise programme.</p>

<h2>Sources</h2>
<ul>
<li><a href="https://www.who.int" target="_blank" rel="noopener">World Health Organization (WHO)</a></li>
<li><a href="https://www.nhs.uk" target="_blank" rel="noopener">NHS (UK)</a></li>
<li><a href="https://www.cdc.gov" target="_blank" rel="noopener">Centers for Disease Control and Prevention (CDC)</a></li>
</ul>
HTML,
	),
	array(
		'title'   => 'Myths and Facts About Daily Water Intake',
		'slug'    => 'myths-facts-daily-water-intake',
		'cat'     => 'myths-vs-facts',
		'excerpt' => 'The "eight glasses a day" rule is widely repeated — but where does it actually come from?',
		'content' => <<<HTML
<h2>Where Did "Eight Glasses a Day" Come From?</h2>
<p>The idea that everyone should drink eight glasses (around two litres) of water per day is one of the most persistent wellness rules around. Interestingly, there is no strong scientific consensus behind this specific number. It appears to be a rough interpretation of a 1945 US recommendation that was later repeated without the important context: that most of that water comes from food.</p>

<h2>How Much Water Do You Actually Need?</h2>
<p>Your body's water needs depend on your size, activity level, the climate you live in, and what you eat. People who eat plenty of fruits and vegetables take in significant amounts of water through food. Those who are very active, live in hot climates, or are pregnant or breastfeeding need more than average.</p>
<p>A practical guide used by many health organisations is to drink when you are thirsty and to pay attention to the colour of your urine: pale yellow suggests adequate hydration, while dark yellow or amber suggests you may need to drink more.</p>

<h2>Can You Drink Too Much Water?</h2>
<p>Yes, though this is rare in everyday situations. Drinking very large quantities of water over a short period can dilute electrolytes in the blood to dangerous levels. This is not a risk from normal drinking habits but can occur in certain endurance sports if plain water is consumed in excessive amounts without replacing electrolytes.</p>

<h2>Does Coffee Dehydrate You?</h2>
<p>Caffeinated drinks like coffee do have a mild diuretic effect, but the fluid they contain still contributes to your overall hydration. For most people who drink coffee in normal amounts, it does not cause dehydration. This myth likely comes from the observation that caffeine increases urine output slightly, but the net effect on hydration is small.</p>

<h2>Practical Takeaway</h2>
<p>Rather than counting glasses, focus on drinking regularly throughout the day, especially before, during, and after exercise. Keep water easily accessible at your desk or in your bag. If you find plain water unappealing, adding a slice of lemon, cucumber, or a few mint leaves can make it easier to drink more.</p>

<h2>Sources</h2>
<ul>
<li><a href="https://www.who.int" target="_blank" rel="noopener">World Health Organization (WHO)</a></li>
<li><a href="https://www.nhs.uk" target="_blank" rel="noopener">NHS (UK)</a></li>
<li><a href="https://www.cdc.gov" target="_blank" rel="noopener">Centers for Disease Control and Prevention (CDC)</a></li>
</ul>
HTML,
	),
	array(
		'title'   => 'Stress and Your Body: Small Habits That Help',
		'slug'    => 'stress-and-your-body-small-habits',
		'cat'     => 'stress-mind',
		'excerpt' => 'Understanding how stress affects the body and the small daily habits that can make a real difference.',
		'content' => <<<HTML
<h2>What Happens When You Are Stressed?</h2>
<p>When you encounter a stressful situation, your body activates a chain of physiological responses. Adrenaline and cortisol are released, your heart rate increases, and your muscles tense up — a response sometimes described as "fight or flight." This is a normal and useful reaction to genuine threats or time-sensitive challenges.</p>
<p>The issue arises when this response is activated repeatedly or stays switched on for extended periods, as it can with ongoing work pressures, financial worries, or relationship difficulties.</p>

<h2>How Chronic Stress Affects the Body</h2>
<p>Long-term stress is associated with a range of physical effects: disrupted sleep, headaches, digestive changes, reduced immune function, and muscle tension. It can also affect concentration, mood, and motivation. These effects are cumulative — the longer stress goes unaddressed, the more it tends to build up.</p>

<h2>Small Habits That Have Evidence Behind Them</h2>
<p>You do not need to overhaul your life to reduce everyday stress. Some of the most well-supported habits are also the simplest:</p>
<ul>
<li><strong>Brief physical activity.</strong> Even a 10-minute walk can lower cortisol levels and improve mood, according to multiple studies.</li>
<li><strong>Regular sleep.</strong> Sleep and stress have a bidirectional relationship: stress disrupts sleep, and poor sleep increases stress. Prioritising consistent sleep times breaks this cycle.</li>
<li><strong>Controlled breathing.</strong> Slow, deliberate breathing activates the parasympathetic nervous system, which counters the stress response. Even a few minutes of slow breathing can produce a noticeable effect.</li>
<li><strong>Reducing caffeine in the afternoon.</strong> Caffeine amplifies the physiological stress response and disrupts sleep — both of which feed back into stress levels.</li>
<li><strong>Social connection.</strong> Brief, positive social interactions — even a short conversation with a colleague or friend — are consistently associated with lower stress markers.</li>
</ul>

<h2>When to Seek Support</h2>
<p>If stress is significantly affecting your daily life, work, or relationships, speaking to your doctor or a mental health professional is a worthwhile step. These habits are supportive tools, not replacements for professional help when it is needed.</p>

<h2>Sources</h2>
<ul>
<li><a href="https://www.who.int" target="_blank" rel="noopener">World Health Organization (WHO)</a></li>
<li><a href="https://www.nhs.uk" target="_blank" rel="noopener">NHS (UK)</a></li>
<li><a href="https://www.cdc.gov" target="_blank" rel="noopener">Centers for Disease Control and Prevention (CDC)</a></li>
</ul>
HTML,
	),
	array(
		'title'   => 'A Beginner Guide to Reading Food Labels',
		'slug'    => 'beginner-guide-reading-food-labels',
		'cat'     => 'nutrition-eating',
		'excerpt' => 'Food labels contain a lot of information. This guide focuses on what actually matters.',
		'content' => <<<HTML
<h2>Why Food Labels Matter</h2>
<p>Food packaging is designed to sell products, not necessarily to make nutritional information easy to understand. Learning to read labels critically takes a little practice but becomes quick and intuitive once you know what to look for.</p>

<h2>Start With Serving Size</h2>
<p>All the nutritional information on a label is given per serving or per 100g. The serving size stated may be much smaller than what you would typically eat. Always check whether you are comparing products on the same basis — either both per serving or both per 100g.</p>

<h2>Ingredients List</h2>
<p>Ingredients are listed in order of weight, from largest to smallest. This means that the first few ingredients make up most of the product. If sugar, salt, or a type of fat appears in the first three ingredients of a savoury product, that is worth noting.</p>
<p>Sugar has many names on labels: sucrose, fructose, glucose syrup, dextrose, maltose, and corn syrup are all forms of added sugar. Spotting these helps you get a more accurate picture of a product's sugar content.</p>

<h2>Nutrients to Pay Attention To</h2>
<p>The key nutrients most health guidelines recommend keeping an eye on are:</p>
<ul>
<li><strong>Saturated fat</strong> — high amounts are linked to raised cholesterol levels over time.</li>
<li><strong>Added sugars</strong> — distinct from naturally occurring sugars in fruit and dairy.</li>
<li><strong>Sodium (salt)</strong> — many packaged foods contain more salt than you might expect.</li>
<li><strong>Fibre</strong> — most people eat less than the recommended daily amount.</li>
</ul>

<h2>Traffic Light Labelling</h2>
<p>Many products now use a colour-coded system (red, amber, green) for key nutrients. Green means the amount is low, amber is medium, and red means the amount is high. This system is not universal across all countries but is widely used in the UK and some other markets.</p>

<h2>A Quick Practical Approach</h2>
<p>Rather than analysing every label in detail, a useful habit is to glance at the ingredients list first (shorter tends to mean less processed) and then check the per-100g figures for sugar, saturated fat, and salt. Comparing two similar products side by side using the per-100g column is often the fastest way to make a better choice.</p>

<h2>Sources</h2>
<ul>
<li><a href="https://www.who.int" target="_blank" rel="noopener">World Health Organization (WHO)</a></li>
<li><a href="https://www.nhs.uk" target="_blank" rel="noopener">NHS (UK)</a></li>
<li><a href="https://www.cdc.gov" target="_blank" rel="noopener">Centers for Disease Control and Prevention (CDC)</a></li>
</ul>
HTML,
	),
	array(
		'title'   => 'The Science of Rest: Active vs Passive Recovery',
		'slug'    => 'science-of-rest-active-vs-passive-recovery',
		'cat'     => 'sleep-recovery',
		'excerpt' => 'Not all rest is equal. Understanding the difference between active and passive recovery can help you bounce back faster.',
		'content' => <<<HTML
<h2>What Is Recovery?</h2>
<p>Recovery is the process by which the body repairs itself after physical or mental exertion. It involves restoring glycogen (stored energy), repairing muscle fibres, reducing inflammation, and resetting the nervous system. Without adequate recovery, performance declines and the risk of injury or burnout increases.</p>

<h2>Passive Recovery</h2>
<p>Passive recovery means rest without any structured activity — sleep, sitting, lying down. It is essential and irreplaceable. During deep sleep in particular, the body produces growth hormone, which is central to tissue repair. No amount of active recovery can substitute for sufficient sleep.</p>

<h2>Active Recovery</h2>
<p>Active recovery involves low-intensity movement performed in the period after harder exercise. Examples include a gentle walk, easy cycling, yoga, or light swimming. The idea is to increase blood circulation — which helps clear metabolic byproducts from muscles — without adding significant new physical stress.</p>
<p>Research suggests that light movement on rest days can reduce delayed-onset muscle soreness (DOMS) and help people feel ready for their next session sooner than complete rest alone.</p>

<h2>Which Is Better?</h2>
<p>The honest answer is: both, depending on context. After very hard physical effort or illness, passive rest is usually more appropriate. After moderate effort or when soreness is mild, active recovery tends to be beneficial. The key is to keep active recovery genuinely low intensity — if it feels like a workout, it is not recovery.</p>

<h2>Recovery for Mental Fatigue</h2>
<p>Recovery matters for mental effort too, not just physical. After cognitively demanding work, passive rest (a nap, quiet time, time in nature) and activities that are absorbing but non-demanding (a creative hobby, a walk without a podcast) tend to restore focus better than more screen time or consumption of information.</p>

<h2>Practical Application</h2>
<p>Building deliberate recovery into your week — planning at least one day of gentle movement and prioritising sleep consistently — tends to produce better results over time than simply training as often as possible. Progress happens during recovery, not during the training itself.</p>

<h2>Sources</h2>
<ul>
<li><a href="https://www.who.int" target="_blank" rel="noopener">World Health Organization (WHO)</a></li>
<li><a href="https://www.nhs.uk" target="_blank" rel="noopener">NHS (UK)</a></li>
<li><a href="https://www.cdc.gov" target="_blank" rel="noopener">Centers for Disease Control and Prevention (CDC)</a></li>
</ul>
HTML,
	),
	array(
		'title'   => 'Starting a Walking Routine: A Simple Approach',
		'slug'    => 'starting-a-walking-routine',
		'cat'     => 'fitness-movement',
		'excerpt' => 'Walking is one of the most accessible and effective forms of regular physical activity.',
		'content' => <<<HTML
<h2>Why Walking?</h2>
<p>Walking requires no equipment, no gym membership, and no prior fitness level. It is low-impact (meaning it places less stress on joints than running), can be done almost anywhere, and fits around most schedules. Despite its simplicity, regular brisk walking is associated with meaningful reductions in the risk of cardiovascular disease, type 2 diabetes, and depression.</p>

<h2>Getting Started</h2>
<p>If you are currently inactive, starting with 10 to 15 minutes of walking per day is a reasonable and sustainable beginning. The goal is to build a habit, not to push yourself hard. After a week or two of consistency, you can gradually increase the duration or pace.</p>
<p>The word "brisk" in public health guidelines means walking at a pace where you feel slightly warmer and are breathing a little harder than normal, but can still hold a conversation comfortably.</p>

<h2>Building Consistency</h2>
<p>Consistency matters more than intensity at the start. Some strategies that help include:</p>
<ul>
<li>Walking at the same time each day so it becomes automatic.</li>
<li>Having a fixed route that you enjoy or find interesting.</li>
<li>Linking the walk to something you already do, such as walking during a lunch break or after dropping children at school.</li>
<li>Tracking your steps or distance, not competitively, but as a way of noticing progress.</li>
</ul>

<h2>Increasing the Challenge</h2>
<p>Once a 30-minute brisk walk feels comfortable, there are several ways to progress: walking faster, walking uphill, adding a backpack for resistance, or incorporating brief intervals of faster movement. These changes can maintain the cardiovascular challenge as your fitness improves.</p>

<h2>Walking and Mental Wellbeing</h2>
<p>Beyond the physical benefits, walking — particularly in natural environments — is consistently associated with improvements in mood and reductions in self-reported stress. Even short walks outdoors can provide a meaningful break from screen time and indoor environments.</p>

<h2>Sources</h2>
<ul>
<li><a href="https://www.who.int" target="_blank" rel="noopener">World Health Organization (WHO)</a></li>
<li><a href="https://www.nhs.uk" target="_blank" rel="noopener">NHS (UK)</a></li>
<li><a href="https://www.cdc.gov" target="_blank" rel="noopener">Centers for Disease Control and Prevention (CDC)</a></li>
</ul>
HTML,
	),
	array(
		'title'   => 'Understanding Portion Sizes Without Counting Calories',
		'slug'    => 'understanding-portion-sizes',
		'cat'     => 'nutrition-eating',
		'excerpt' => 'Practical ways to gauge portion sizes using everyday references rather than scales or apps.',
		'content' => <<<HTML
<h2>Why Portions Matter</h2>
<p>It is entirely possible to eat a diet that includes good, nutritious foods and still consume more than your body needs, simply because portion sizes have grown considerably larger than the standard serving sizes used in nutritional guidelines. Understanding roughly how much of each food group is a reasonable serving helps you make consistent choices without needing to track every gram.</p>

<h2>Using Your Hand as a Guide</h2>
<p>One of the most practical portion guides is your own hand, which scales broadly with your body size:</p>
<ul>
<li><strong>A fist</strong> is roughly one serving of whole grains or starchy vegetables.</li>
<li><strong>A cupped hand</strong> is roughly one serving of raw nuts, seeds, or dried fruit.</li>
<li><strong>A palm</strong> is roughly one serving of protein (meat, fish, or legumes).</li>
<li><strong>Your thumb</strong> is roughly one serving of fats like cheese or nut butter.</li>
</ul>
<p>These are approximations, not precise measurements, but they provide a useful everyday reference without any equipment.</p>

<h2>Plate Proportions</h2>
<p>Rather than measuring food, thinking in terms of plate proportions can be effective. Aiming for roughly half your plate to be vegetables or salad, a quarter whole grains, and a quarter protein creates a reasonable balance for most main meals without any calculation.</p>

<h2>Eating Slowly</h2>
<p>Hunger and fullness signals take around 15 to 20 minutes to register. Eating quickly means it is easy to consume more than needed before those signals reach your brain. Slowing down — putting your fork down between bites, chewing thoroughly — is a simple change that many people find helps them feel satisfied with smaller amounts.</p>

<h2>Portion Distortion</h2>
<p>Restaurant portions and packaged food serving sizes have grown over decades. When eating out, it can help to decide before ordering whether you will eat the full portion or take half home, rather than making that decision when the food is in front of you. Smaller plates at home also genuinely affect how much people serve themselves.</p>

<h2>Sources</h2>
<ul>
<li><a href="https://www.who.int" target="_blank" rel="noopener">World Health Organization (WHO)</a></li>
<li><a href="https://www.nhs.uk" target="_blank" rel="noopener">NHS (UK)</a></li>
<li><a href="https://www.cdc.gov" target="_blank" rel="noopener">Centers for Disease Control and Prevention (CDC)</a></li>
</ul>
HTML,
	),
	array(
		'title'   => 'Sleep and Mood: How Rest Affects How You Feel',
		'slug'    => 'sleep-and-mood',
		'cat'     => 'sleep-recovery',
		'excerpt' => 'The link between sleep and emotional wellbeing is well established. Here is what the research shows.',
		'content' => <<<HTML
<h2>The Sleep-Mood Connection</h2>
<p>Most people have experienced the effect of a poor night's sleep on their mood — irritability, reduced patience, difficulty concentrating, and a tendency to interpret neutral situations more negatively. These are not simply a matter of willpower. Sleep deprivation has measurable effects on the parts of the brain that regulate emotional responses.</p>

<h2>What Research Shows</h2>
<p>Studies using brain imaging consistently show that sleep-deprived people show stronger emotional reactions to negative stimuli and weaker ability to regulate those reactions compared to people who are well-rested. The amygdala — a brain region central to emotional processing — becomes more reactive with insufficient sleep, while the prefrontal cortex, which helps regulate behaviour and decision-making, becomes less active.</p>

<h2>The Relationship Goes Both Ways</h2>
<p>While poor sleep affects mood, low mood and anxiety also disrupt sleep — often by increasing rumination (repetitive thinking) at bedtime or causing early morning waking. This bidirectional relationship means that addressing sleep and mood together tends to be more effective than focusing on either alone.</p>

<h2>Practical Implications</h2>
<p>If you notice patterns of low mood, irritability, or increased anxiety, it is worth evaluating your sleep as one contributing factor. Even modest improvements in sleep — going to bed 30 minutes earlier consistently, reducing screen time before sleep, or making the bedroom darker and cooler — can produce noticeable mood improvements within a few weeks for many people.</p>

<h2>When to Talk to Someone</h2>
<p>Persistent low mood or anxiety that significantly affects daily life is worth discussing with a healthcare professional, regardless of its relationship to sleep. Sleep improvements can support wellbeing, but they are not a treatment for clinical depression or anxiety disorders.</p>

<h2>Sources</h2>
<ul>
<li><a href="https://www.who.int" target="_blank" rel="noopener">World Health Organization (WHO)</a></li>
<li><a href="https://www.nhs.uk" target="_blank" rel="noopener">NHS (UK)</a></li>
<li><a href="https://www.cdc.gov" target="_blank" rel="noopener">Centers for Disease Control and Prevention (CDC)</a></li>
</ul>
HTML,
	),
	array(
		'title'   => 'Five Breathing Techniques to Reduce Everyday Stress',
		'slug'    => 'five-breathing-techniques-stress',
		'cat'     => 'stress-mind',
		'excerpt' => 'Simple breathing exercises that can be done anywhere to calm your nervous system and reduce stress.',
		'content' => <<<HTML
<h2>Why Breathing Works</h2>
<p>Controlled breathing is one of the few ways to directly influence the autonomic nervous system — the system that governs your body's stress and relaxation responses. Slow, deliberate breathing activates the parasympathetic nervous system, which counters the physiological effects of stress including elevated heart rate, muscle tension, and cortisol release.</p>
<p>The techniques below are well-documented and can be used anywhere with no equipment.</p>

<h2>1. Diaphragmatic (Belly) Breathing</h2>
<p>Place one hand on your chest and one on your stomach. Breathe in through your nose for 4 seconds, allowing your stomach to rise rather than your chest. Breathe out slowly through your mouth for 6 seconds. Repeat for 2 to 5 minutes. This is a foundational technique used in stress management programmes worldwide.</p>

<h2>2. Box Breathing</h2>
<p>Breathe in for 4 seconds, hold for 4 seconds, breathe out for 4 seconds, hold for 4 seconds. Repeat the cycle 4 to 6 times. This technique is used in a range of high-pressure professional contexts precisely because it is effective and fast-acting.</p>

<h2>3. 4-7-8 Breathing</h2>
<p>Inhale through your nose for 4 seconds, hold for 7 seconds, exhale completely through your mouth for 8 seconds. The extended exhale is key — longer exhales are particularly effective at activating the relaxation response. This is a good technique to use before sleep.</p>

<h2>4. Paced Breathing</h2>
<p>Simply slow your breathing rate to around 5 to 6 breaths per minute (versus a typical 12 to 20). This can be done by inhaling for 5 seconds and exhaling for 5 seconds. Regular practice has been shown in research to lower resting heart rate and reduce anxiety over time.</p>

<h2>5. Humming Exhale</h2>
<p>Inhale through your nose, then exhale slowly while making a soft humming sound. The vibration activates the vagus nerve, which plays a central role in the relaxation response. This can feel slightly unusual at first but becomes easier with practice.</p>

<h2>Building a Habit</h2>
<p>Even 2 to 3 minutes of controlled breathing once or twice per day can produce cumulative benefits. Many people find it helpful to link the practice to something they already do — their morning routine, a lunch break, or the moment before an activity they find stressful.</p>

<h2>Sources</h2>
<ul>
<li><a href="https://www.who.int" target="_blank" rel="noopener">World Health Organization (WHO)</a></li>
<li><a href="https://www.nhs.uk" target="_blank" rel="noopener">NHS (UK)</a></li>
<li><a href="https://www.cdc.gov" target="_blank" rel="noopener">Centers for Disease Control and Prevention (CDC)</a></li>
</ul>
HTML,
	),
);

// Get existing posts and assign articles.
$existing_posts = get_posts( array(
	'post_status' => 'any',
	'numberposts' => 20,
	'post_type'   => 'post',
	'orderby'     => 'ID',
	'order'       => 'ASC',
) );

foreach ( $existing_posts as $i => $post ) {
	if ( get_post_meta( $post->ID, '_demo_rewritten', true ) === '1' ) {
		continue; // Already done.
	}
	$a = isset( $articles[ $i ] ) ? $articles[ $i ] : $articles[ count( $articles ) - 1 ];

	// Get category term_id.
	$cat_term = get_term_by( 'slug', $a['cat'], 'category' );
	$cat_ids  = $cat_term ? array( $cat_term->term_id ) : array();

	wp_update_post( array(
		'ID'           => $post->ID,
		'post_title'   => $a['title'],
		'post_name'    => $a['slug'],
		'post_excerpt' => $a['excerpt'],
		'post_content' => $a['content'],
		'post_status'  => 'publish',
	) );
	wp_set_post_categories( $post->ID, $cat_ids );
	update_post_meta( $post->ID, '_demo_rewritten', '1' );
	$counts['posts']++;
	echo "Post updated: {$a['title']}\n";
}

// =========================================================================
// 4. CONSULTATION TYPES
// =========================================================================
$type_defs = array(
	array(
		'title' => 'Nutrition Consultation',
		'dur'   => 30,
		'fee'   => '25.00',
		'desc'  => 'A 30-minute one-on-one session focusing on your eating habits and nutrition goals. Demo only — no real service is provided.',
		'modes' => 'video,chat',
	),
	array(
		'title' => 'Sleep & Stress Coaching',
		'dur'   => 45,
		'fee'   => '35.00',
		'desc'  => 'A 45-minute session covering sleep routines and practical stress management strategies. Demo only — no real service is provided.',
		'modes' => 'video,chat',
	),
	array(
		'title' => 'Fitness Plan Review',
		'dur'   => 30,
		'fee'   => '25.00',
		'desc'  => 'A 30-minute review of your current fitness activity and wellness goals. Demo only — no real service is provided.',
		'modes' => 'video,chat',
	),
);

foreach ( $type_defs as $td ) {
	// Idempotent: check if title already exists.
	$existing = get_page_by_title( $td['title'], OBJECT, 'wc_consult_type' );
	if ( $existing ) {
		echo "Type already exists: {$td['title']}\n";
		continue;
	}
	$type_id = wp_insert_post( array(
		'post_title'  => $td['title'],
		'post_type'   => 'wc_consult_type',
		'post_status' => 'publish',
		'post_content' => $td['desc'],
	) );
	if ( ! is_wp_error( $type_id ) ) {
		update_post_meta( $type_id, '_wc_duration', $td['dur'] );
		update_post_meta( $type_id, '_wc_fee_usd',  $td['fee'] );
		update_post_meta( $type_id, '_wc_description', $td['desc'] );
		update_post_meta( $type_id, '_wc_modes',    $td['modes'] );
		update_post_meta( $type_id, '_wc_active',   1 );
		$counts['types']++;
		echo "Created type: {$td['title']}\n";
	}
}

// =========================================================================
// 5. CONSULTANT USERS
// =========================================================================
$consultant_defs = array(
	array(
		'login_contains' => 'consultant_test_a',
		'display'        => 'Demo Consultant - Nutrition',
		'bio'            => 'Demo wellness coach specialising in everyday nutrition and healthy eating habits. All sessions shown are for portfolio demonstration only.',
	),
	array(
		'login_contains' => 'consultant_test_b',
		'display'        => 'Demo Consultant - Sleep & Fitness',
		'bio'            => 'Demo wellness coach covering sleep improvement and fitness routines. All sessions shown are for portfolio demonstration only.',
	),
);

$availability = array(
	'monday'    => array( 'enabled' => true,  'start' => '10:00', 'end' => '16:00' ),
	'tuesday'   => array( 'enabled' => true,  'start' => '10:00', 'end' => '16:00' ),
	'wednesday' => array( 'enabled' => true,  'start' => '10:00', 'end' => '16:00' ),
	'thursday'  => array( 'enabled' => true,  'start' => '10:00', 'end' => '16:00' ),
	'friday'    => array( 'enabled' => true,  'start' => '10:00', 'end' => '16:00' ),
	'saturday'  => array( 'enabled' => false, 'start' => '',      'end' => '' ),
	'sunday'    => array( 'enabled' => false, 'start' => '',      'end' => '' ),
);

$consultants_all = get_users( array( 'role' => 'wc_consultant', 'number' => 10 ) );
$cred_lines      = array( '# Demo Consultant Credentials', '', 'These accounts are for portfolio demonstration only. No real services are provided.', '' );

foreach ( $consultants_all as $idx => $cu ) {
	$def = isset( $consultant_defs[ $idx ] ) ? $consultant_defs[ $idx ] : $consultant_defs[ count( $consultant_defs ) - 1 ];
	wp_update_user( array(
		'ID'           => $cu->ID,
		'display_name' => $def['display'],
	) );
	update_user_meta( $cu->ID, '_wc_bio',          $def['bio'] );
	update_user_meta( $cu->ID, '_wc_availability', wp_json_encode( $availability ) );
	$counts['consultants']++;
	$cred_lines[] = "## {$def['display']}";
	$cred_lines[] = "- Username: {$cu->user_login}";
	$cred_lines[] = "- Email: {$cu->user_email}";
	$cred_lines[] = "- Role: wc_consultant (demo only)";
	$cred_lines[] = "- Availability: Mon-Fri 10:00-16:00 site timezone";
	$cred_lines[] = '';
	echo "Updated consultant: {$def['display']}\n";
}

$cred_lines[] = '> **Note:** To log in as a consultant, use the WordPress admin login page and the credentials above.';
$cred_lines[] = '> No real password is listed here. Reset via the admin panel as needed.';

$docs_dir = dirname( __DIR__ ) . '/docs';
if ( ! is_dir( $docs_dir ) ) {
	mkdir( $docs_dir, 0755, true );
}
$cred_file = $docs_dir . '/demo-credentials.md';
if ( ! file_exists( $cred_file ) ) {
	file_put_contents( $cred_file, implode( "\n", $cred_lines ) . "\n" );
	echo "Created: docs/demo-credentials.md\n";
}

// Add to .gitignore.
$gitignore = dirname( __DIR__ ) . '/.gitignore';
$gi_content = file_exists( $gitignore ) ? file_get_contents( $gitignore ) : '';
if ( false === strpos( $gi_content, 'demo-credentials.md' ) ) {
	file_put_contents( $gitignore, $gi_content . "\n# Demo credentials\ndocs/demo-credentials.md\n" );
	echo "Updated .gitignore\n";
}

// =========================================================================
// 6. CUSTOMIZER MODS
// =========================================================================
$theme_key  = 'theme_mods_wellness-theme';
$theme_mods = get_option( $theme_key, array() );

$mod_updates = array(
	'wellness_topbar_text'          => 'Practical • Evidence-informed • Everyday Wellness',
	'wellness_hero_label'           => 'Everyday wellness, explained',
	'wellness_hero_title'           => 'Practical guidance for a healthier everyday life',
	'wellness_hero_subtitle'        => 'Clear, well-sourced articles on nutrition, sleep, fitness and stress management.',
	'wellness_hero_btn1_text'       => 'Read the Articles',
	'wellness_hero_btn2_text'       => 'Browse Topics',
	'wellness_trust_1'              => 'Evidence-informed',
	'wellness_trust_2'              => 'Plain language',
	'wellness_trust_3'              => 'Practical advice',
	'wellness_topics_label'         => 'EXPLORE TOPICS',
	'wellness_topics_title'         => 'Browse by Wellness Topic',
	'wellness_topics_subtitle'      => 'Articles covering nutrition, sleep, fitness and everyday stress management.',
	'wellness_articles_label'       => 'LATEST ARTICLES',
	'wellness_articles_title'       => 'Recent Wellness Articles',
	'wellness_articles_btn_text'    => 'View all articles',
	'wellness_myths_label'          => 'MYTHS AND FACTS',
	'wellness_myths_title'          => 'Common Wellness Myths vs The Evidence',
	'wellness_myths_subtitle'       => 'Replacing guesswork with evidence-informed clarity.',
	'wellness_myth_1_text'          => '"You need to eat less to lose weight, no matter what you eat."',
	'wellness_fact_1_text'          => 'Food quality, meal timing, and individual metabolism all play a role. Calorie balance matters, but it is not the only factor in body composition.',
	'wellness_myth_2_text'          => '"Eight hours of sleep is the magic number for everyone."',
	'wellness_fact_2_text'          => 'Sleep needs vary by person, age, and activity level. Most adults need 7-9 hours, but quality often matters as much as quantity.',
	'wellness_faq_label'            => 'COMMON QUESTIONS',
	'wellness_faq_title'            => 'Frequently Asked Questions',
	'wellness_faq_intro'            => 'Everything you need to know about how the demo works.',
	'wellness_faq_q1'               => 'Who writes the content?',
	'wellness_faq_a1'               => 'Articles are written by the editorial team using reputable public-health sources including WHO, NHS, and CDC.',
	'wellness_faq_q2'               => 'Is this a real consultation service?',
	'wellness_faq_a2'               => 'No. This is a portfolio demonstration website. Consultants are fictional and no real services or payments are provided.',
	'wellness_faq_q3'               => 'How does the booking demo work?',
	'wellness_faq_a3'               => 'You can walk through the entire booking flow to see how it works. No real payment is taken in demo mode.',
	'wellness_cta_title'            => 'See How the Booking System Works',
	'wellness_cta_text'             => 'Explore the demo booking flow end to end. No real payment is processed and no real consultation takes place.',
	'wellness_cta_btn_text'         => 'Try the Demo Booking',
	'wellness_footer_tagline'       => 'Everyday wellness guidance. Portfolio demonstration website.',
	'wellness_footer_disclaimer'    => 'This is a portfolio demonstration website. All content is for demonstration purposes only. No real consultations, medical advice, or payments are provided. Consultants shown are fictional.',
	'wellness_footer_emergency'     => 'In a real emergency, please contact your local emergency services or visit the nearest hospital immediately.',
	'wellness_footer_copyright'     => '',
	'wellness_mobile_tagline'       => 'Everyday Wellness Demo',
	'wellness_demo_notice_on'       => true,
	'wellness_demo_notice_text'     => 'Portfolio demo website. Content is for demonstration only. No real consultations or payments.',
	'wellness_show_quick_exit'      => false,
);

foreach ( $mod_updates as $key => $value ) {
	$theme_mods[ $key ] = $value;
}
update_option( $theme_key, $theme_mods );
echo "Updated customizer mods.\n";

// =========================================================================
// 7. PAGE CONTENT — neutral demo text.
// =========================================================================
$page_updates = array(
	'about' => array(
		'title'   => 'About This Demo',
		'content' => '<p><strong>This is a portfolio demonstration website.</strong> It showcases a fully functional wellness consultation booking system built with WordPress. All consultants shown are fictional, all bookings are demonstrations, and no real services or payments are provided.</p>

<p>The site covers everyday wellness topics including nutrition, sleep, fitness, and stress management. Articles are written to illustrate content quality and are based on reputable public-health sources. They are not intended as medical advice.</p>

<h2>What the Demo Shows</h2>
<p>The site demonstrates a complete consultation booking flow: selecting a service type, choosing a time slot, completing payment (in test mode), receiving confirmation emails, and tracking request status. All of this runs on a custom WordPress plugin built for this portfolio.</p>

<h2>Editorial Approach</h2>
<p>Content on this site is written in plain language using public-health sources including the World Health Organization, NHS, and CDC. Articles do not make medical claims, do not recommend specific products or supplements, and are updated when guidance changes.</p>

<h2>Contact</h2>
<p>This is a demo — the contact form does not route to a real support team. If you are evaluating this portfolio, please get in touch through the contact details provided in the portfolio itself.</p>',
	),
	'contact' => array(
		'title'   => 'Contact',
		'content' => '<p>This is a portfolio demonstration website. The form below is part of the demo and does not route to a real support team.</p>
<p>If you have questions about this portfolio project, please use the contact details provided in the portfolio itself.</p>
[contact-form-7 id="contact-form" title="Contact form"]',
	),
	'privacy-policy' => array(
		'title'   => 'Privacy Policy',
		'content' => '<p><strong>Demo notice:</strong> This is a portfolio demonstration website. This privacy policy is provided as a demonstration of a real policy structure. No real personal data is collected for commercial purposes on this demo site.</p>

<h2>What Data We Collect</h2>
<p>When you use the booking or contact forms on this demonstration site, the information you provide (name, email, message) is stored in the WordPress database on the local or staging server used for this demo. It is not shared with third parties.</p>

<h2>Cookies</h2>
<p>This site uses cookies for essential functions (session management, the cookie consent preference you set). No advertising or tracking cookies are used.</p>

<h2>Payment Data</h2>
<p>All payments on this demo site are processed in test mode. No real payment card data is collected or stored. Test payments use sandbox credentials provided by Stripe or PayPal.</p>

<h2>Your Rights</h2>
<p>As this is a demonstration site, there is no stored personal data beyond what you voluntarily submit through the demo forms. You can request deletion of any submitted data by contacting the site owner.</p>

<h2>Changes to This Policy</h2>
<p>This policy may be updated as the demo develops. The date at the top of the page reflects the most recent revision.</p>',
	),
	'terms-of-service' => array(
		'title'   => 'Terms and Conditions',
		'content' => '<p><strong>Demo notice:</strong> This is a portfolio demonstration website. The terms below are provided to illustrate a real Terms and Conditions structure. No real services, contracts, or legal obligations exist through this demo site.</p>

<h2>Use of This Site</h2>
<p>This demonstration website is provided for portfolio evaluation purposes. By using it, you agree that you are doing so for evaluation purposes only and understand that all bookings, payments, and consultations shown are fictional demonstrations.</p>

<h2>Content</h2>
<p>Wellness articles on this site are written for demonstration purposes and to illustrate content quality. They are based on reputable public-health sources but should not be treated as personal medical advice. Always consult a qualified healthcare professional for personal health concerns.</p>

<h2>Bookings and Payments</h2>
<p>All bookings and payments on this site are in demo mode. No real money is taken. Consultants shown are fictional. No real consultation, diagnosis, or treatment is provided.</p>

<h2>Limitation of Liability</h2>
<p>The site owner accepts no liability for decisions made based on content displayed on this demonstration site.</p>',
	),
	'medical-disclaimer' => array(
		'title'   => 'Medical Disclaimer',
		'content' => '<p><strong>Portfolio Demo Notice:</strong> This is a demonstration website built to showcase a wellness consultation booking system. All consultants are fictional. No real medical advice, diagnosis, or treatment is provided through this site.</p>

<h2>Educational Content Only</h2>
<p>All articles on this site are written for general informational and educational purposes only. They are based on publicly available information from reputable health organisations including the World Health Organization, NHS, and CDC.</p>

<h2>Not a Substitute for Professional Advice</h2>
<p>Nothing on this website should be treated as professional medical advice. If you have a health concern, please consult a qualified healthcare professional.</p>

<h2>No Emergency Services</h2>
<p>This site does not provide emergency medical services. If you are in an emergency, please contact your local emergency services immediately.</p>',
	),
	'refund-policy' => array(
		'title'   => 'Refund and Rescheduling Policy',
		'content' => '<p><strong>Demo notice:</strong> This is a portfolio demonstration. No real payments are taken and no real refunds are processed. This page demonstrates the structure of a real refund policy.</p>

<h2>Cancellations Before Scheduling</h2>
<p>If a booking is cancelled before a time slot is assigned, a full refund of the session fee is issued automatically.</p>

<h2>Rescheduling</h2>
<p>Clients are entitled to one free reschedule per booking, provided the request is made at least 6 hours before the scheduled session time.</p>

<h2>Cancellations After Scheduling</h2>
<p>Cancellations made more than 24 hours before the scheduled time receive a full refund. Cancellations made within 24 hours may receive a partial refund at the discretion of the administrator. No-shows do not qualify for a refund.</p>

<h2>Processing Time</h2>
<p>Refunds for card and PayPal payments are initiated within 2 business days and may take 5 to 10 business days to appear on your statement depending on your bank or payment provider.</p>',
	),
);

foreach ( $page_updates as $slug => $data ) {
	$page = get_page_by_path( $slug );
	if ( ! $page ) {
		echo "WARNING: Page '$slug' not found.\n";
		continue;
	}
	if ( get_post_meta( $page->ID, '_demo_rewritten', true ) === '1' ) {
		continue;
	}
	wp_update_post( array(
		'ID'           => $page->ID,
		'post_title'   => $data['title'],
		'post_content' => $data['content'],
		'post_status'  => 'publish',
	) );
	update_post_meta( $page->ID, '_demo_rewritten', '1' );
	$counts['pages']++;
	echo "Updated page: {$slug}\n";
}

// Also update About Us if separate page exists.
$about_us = get_page_by_path( 'about-us' );
if ( $about_us && get_post_meta( $about_us->ID, '_demo_rewritten', true ) !== '1' ) {
	wp_update_post( array(
		'ID'           => $about_us->ID,
		'post_title'   => 'About This Demo',
		'post_content' => $page_updates['about']['content'],
		'post_status'  => 'publish',
	) );
	update_post_meta( $about_us->ID, '_demo_rewritten', '1' );
	$counts['pages']++;
	echo "Updated page: about-us\n";
}

// =========================================================================
// 8. BLOG / SEO SETTINGS
// =========================================================================
update_option( 'blog_public', 0 );
update_option( 'blogdescription', 'Everyday wellness guidance' );
update_option( 'blogname', get_option( 'blogname' ) ); // No change.
echo "Set blog_public=0, blogdescription updated.\n";

// =========================================================================
// 9. PAYMENT SETTINGS
// =========================================================================
update_option( 'wc_payment_test_mode', 1 );
update_option( 'wc_manual_payment_enabled', 1 );
update_option( 'wc_manual_payment_instructions', 'Demo only - please do not send any money. This is a portfolio demonstration.' );
echo "Payment test mode on, manual payment demo instructions set.\n";

// =========================================================================
// SUMMARY
// =========================================================================
echo "\n";
echo "===== DONE =====\n";
echo "Posts rewritten:   {$counts['posts']}\n";
echo "Pages updated:     {$counts['pages']}\n";
echo "Types created:     {$counts['types']}\n";
echo "Consultants updated: {$counts['consultants']}\n";
echo "================\n";
