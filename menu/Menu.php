<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mood-Based Food Recommender</title>
    <link rel="stylesheet" href="../css/Menu.css">
</head>
<body>

    <nav class="navbar">
        <ul>
            <li><a href="../Home.php">Home</a></li>
            <li><a href="../menu/cart.php">Cart</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1 style="text-align: center;">Mood-Based Recommendations</h1>
        
        <form id="search-form" class="search-form">
            <input type="text" id="search-input" name="search" placeholder="Enter your mood or a keyword...">
            <button type="submit">Get Recommendations</button>
        </form>

        <div id="results-container" class="menu">
            <p style="text-align: center;">Your recommendations will appear here.</p>
        </div>
    </div>

    <script>
        // Listen for the form submission
        document.getElementById('search-form').addEventListener('submit', function(event) {
            // Prevent the default form submission that reloads the page
            event.preventDefault();

            const searchQuery = document.getElementById('search-input').value;
            const resultsContainer = document.getElementById('results-container');
            
            // Clear previous results and show a loading message
            resultsContainer.innerHTML = '<p style="text-align: center;">Searching...</p>';

            // Make an API call to your Python backend
            // Make sure your Python server is running on localhost:5000
            fetch('http://127.0.0.1:5000/recommend', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ query: searchQuery }) // Send the user's query
            })
            .then(response => response.json())
            .then(data => {
                // Clear the loading message
                resultsContainer.innerHTML = '';
                
                if (data.recommendations && data.recommendations.length > 0) {
                    // Loop through the recommendations and display them
                    data.recommendations.forEach(foodItem => {
                        // Create a simple div for each food item. You can add more details later.
                        const itemDiv = document.createElement('div');
                        itemDiv.classList.add('menu-item'); // Use your existing CSS class
                        itemDiv.innerHTML = `
                            <div class='menu-details'>
                                <h3>${foodItem}</h3>
                            </div>
                        `;
                        resultsContainer.appendChild(itemDiv);
                    });
                } else {
                    resultsContainer.innerHTML = '<p style="text-align: center;">No recommendations found. Try a different mood or keyword.</p>';
                }
            })
            .catch(error => {
                // Handle any errors that occur during the API call
                resultsContainer.innerHTML = '<p style="text-align: center;">Error fetching recommendations. Please check if your Python server is running.</p>';
                console.error('Error:', error);
            });
        });
    </script>

</body>
</html>