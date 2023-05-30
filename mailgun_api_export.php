<?php
$api_key = "key-c82febc9cb3ff72d34b4051292acadaa";
$domain = "kurs.fiska.no";
$base_url = "https://api.eu.mailgun.net/v3/";

$event_types = ["delivered", "opened", "failed"];
$emails = [];
$email_ids = [];
$next_id = 1;

foreach ($event_types as $event_type) {
    $url = $base_url . $domain . "/events?event=" . $event_type . "&limit=300";

    // Set up a stream context for Basic Auth
    $context = stream_context_create([
        "http" => [
            "header" => "Authorization: Basic " . base64_encode("api:" . $api_key)
        ]
    ]);

    // Get the JSON response
    $response_json = file_get_contents($url, false, $context);
    $response = json_decode($response_json);

    // Process the items from this page
    foreach ($response->items as $item) {
        $email = $item->message->headers->to;

        // Initialize the email's event statuses and ID if necessary
        if (!isset($emails[$email])) {
            // Assign the next available ID to this email
            $email_ids[$email] = $next_id++;
            $emails[$email] = [
                'id' => $email_ids[$email],
                'delivered' => false,
                'opened' => false,
            ];
        }

        // Set the event status for this email
        $emails[$email][$event_type] = true;
    }
}

// Now $emails should contain all email addresses with their delivery and open statuses

// Open a file for writing
$file = fopen('delivered_emails.csv', 'w');

// Write the headers
fputcsv($file, ['ID', 'Email', 'Delivered', 'Opened']);

// Write the data
foreach ($emails as $email => $statuses) {
    fputcsv($file, [$statuses['id'], $email, $statuses['delivered'] ? "yes" : "no", $statuses['opened'] ? "yes" : "no"]);
}

// Close the file
fclose($file);
?>