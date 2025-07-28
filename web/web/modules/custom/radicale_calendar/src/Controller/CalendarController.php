<?php

namespace Drupal\radicale_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Calendar controller for Radicale integration.
 */
class CalendarController extends ControllerBase {

  /**
   * Display the welcome page.
   */
  public function welcome() {
    $build = [
      '#theme' => 'radicale_welcome',
      '#attached' => [
        'library' => [
          'radicale_calendar/radicale_calendar',
        ],
      ],
    ];

    return $build;
  }

  /**
   * Display the calendar page.
   */
  public function calendar() {
    $build = [
      '#theme' => 'radicale_calendar',
      '#attached' => [
        'library' => [
          'radicale_calendar/radicale_calendar',
        ],
      ],
    ];

    return $build;
  }

  /**
   * Parse iCalendar data and extract events.
   */
  private function parseICalendarData($ical_data) {
    $events = [];
    
    // Split the data into lines
    $lines = explode("\n", $ical_data);
    $current_event = null;
    $in_event = false;
    $in_valarm = false;
    
    foreach ($lines as $line) {
      $line = trim($line);
      
      if ($line === 'BEGIN:VEVENT') {
        $in_event = true;
        $in_valarm = false;
        $current_event = [];
      }
      elseif ($line === 'END:VEVENT' && $in_event) {
        $in_event = false;
        $in_valarm = false;
        if (!empty($current_event)) {
          $events[] = $this->convertEventToFullCalendarFormat($current_event);
        }
        $current_event = null;
      }
      elseif ($line === 'BEGIN:VALARM' && $in_event) {
        // Start of alarm section - ignore everything until END:VALARM
        $in_valarm = true;
      }
      elseif ($line === 'END:VALARM' && $in_event) {
        // End of alarm section - resume parsing event properties
        $in_valarm = false;
      }
      elseif ($in_event && !$in_valarm && strpos($line, ':') !== false) {
        // Only parse properties when we're in an event but not in an alarm section
        list($property, $value) = explode(':', $line, 2);
        
        // Handle properties with parameters (e.g., DTSTART;TZID=...)
        if (strpos($property, ';') !== false) {
          $property = explode(';', $property)[0];
        }
        
        $current_event[$property] = $value;
      }
    }
    
    return $events;
  }

  /**
   * Convert parsed event to FullCalendar format.
   */
  private function convertEventToFullCalendarFormat($event) {
    $calendar_event = [];
    
    // Title
    if (isset($event['SUMMARY'])) {
      $calendar_event['title'] = $this->cleanICalendarText($event['SUMMARY']);
    }
    
    // Start date/time
    if (isset($event['DTSTART'])) {
      $calendar_event['start'] = $this->parseICalendarDate($event['DTSTART']);
    }
    
    // End date/time
    if (isset($event['DTEND'])) {
      $calendar_event['end'] = $this->parseICalendarDate($event['DTEND']);
    }
    
    // Use extendedProps to store all additional event information
    $calendar_event['extendedProps'] = [];
    
    // Description
    if (isset($event['DESCRIPTION'])) {
      $calendar_event['extendedProps']['description'] = $this->cleanICalendarText($event['DESCRIPTION']);
    }
    
    // Location
    if (isset($event['LOCATION'])) {
      $calendar_event['extendedProps']['location'] = $this->cleanICalendarText($event['LOCATION']);
    }
    
    // Contact information (can be in multiple iCal fields)
    $contact_info = [];
    
    // Check for ORGANIZER field
    if (isset($event['ORGANIZER'])) {
      $organizer = $event['ORGANIZER'];
      // Extract email from ORGANIZER field (format: ORGANIZER:MAILTO:email@example.com)
      if (preg_match('/MAILTO:([^\s]+)/', $organizer, $matches)) {
        $contact_info['email'] = $matches[1];
      }
      // Extract CN (Common Name) parameter
      if (preg_match('/CN=([^;:]+)/', $organizer, $matches)) {
        $contact_info['name'] = $this->cleanICalendarText(trim($matches[1], '"'));
      }
    }
    
    // Check for ATTENDEE fields (can be multiple)
    if (isset($event['ATTENDEE'])) {
      $attendee = $event['ATTENDEE'];
      if (preg_match('/MAILTO:([^\s]+)/', $attendee, $matches)) {
        $contact_info['attendee_email'] = $matches[1];
      }
      if (preg_match('/CN=([^;:]+)/', $attendee, $matches)) {
        $contact_info['attendee_name'] = $this->cleanICalendarText(trim($matches[1], '"'));
      }
    }
    
    // Check for CONTACT field (if present)
    if (isset($event['CONTACT'])) {
      $contact_info['contact'] = $this->cleanICalendarText($event['CONTACT']);
    }
    
    // Check for URL field
    if (isset($event['URL'])) {
      $contact_info['url'] = $event['URL'];
    }
    
    if (!empty($contact_info)) {
      $calendar_event['extendedProps']['contact'] = $contact_info;
    }
    
    // Store formatted dates for display
    if (isset($calendar_event['start'])) {
      $start_date = new \DateTime($calendar_event['start']);
      $calendar_event['extendedProps']['startDate'] = $start_date->format('m/d/Y'); // mm/dd/yyyy format
      $calendar_event['extendedProps']['startTime'] = $start_date->format('g:i A'); // 12-hour format with AM/PM, no seconds
    }
    
    if (isset($calendar_event['end'])) {
      $end_date = new \DateTime($calendar_event['end']);
      $calendar_event['extendedProps']['endDate'] = $end_date->format('m/d/Y'); // mm/dd/yyyy format
      $calendar_event['extendedProps']['endTime'] = $end_date->format('g:i A'); // 12-hour format with AM/PM, no seconds
    }
    
    return $calendar_event;
  }

  /**
   * Clean iCalendar text by removing escape characters.
   */
  private function cleanICalendarText($text) {
    // Remove iCalendar escape characters
    $text = str_replace('\\,', ',', $text);  // Escaped commas
    $text = str_replace('\\;', ';', $text);  // Escaped semicolons
    $text = str_replace('\\n', "\n", $text); // Escaped newlines
    $text = str_replace('\\\\', '\\', $text); // Escaped backslashes
    
    return $text;
  }

  /**
   * Parse iCalendar date format to ISO 8601.
   */
  private function parseICalendarDate($date_string) {
    // Remove any timezone info for now and handle basic format
    $date_string = preg_replace('/^[^:]*:/', '', $date_string);
    
    // Handle format: 20250625T180000
    if (preg_match('/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})/', $date_string, $matches)) {
      return sprintf('%s-%s-%sT%s:%s:%s', 
        $matches[1], $matches[2], $matches[3], 
        $matches[4], $matches[5], $matches[6]
      );
    }
    
    return $date_string;
  }

  /**
   * API endpoint to fetch events from Radicale server.
   */
  public function eventsApi() {
    \Drupal::logger('radicale_calendar')->info('Events API called');
    
    // Get events from all calendars
    $all_events = [];
    
    // Dynamically discover calendar collections using CalDAV PROPFIND
    $calendars = $this->discoverCalDAVCalendars();
    
    foreach ($calendars as $calendar_url) {
      \Drupal::logger('radicale_calendar')->info('Checking calendar: @url', ['@url' => $calendar_url]);
      
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $calendar_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);
      
      $response = curl_exec($ch);
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      
      \Drupal::logger('radicale_calendar')->info('Calendar @url response code: @code', ['@url' => $calendar_url, '@code' => $http_code]);
      
      // Parse the calendar data if we got a successful response
      if ($http_code === 200 && !empty($response)) {
        $events = $this->parseICalendarData($response);
        \Drupal::logger('radicale_calendar')->info('Parsed @count events from @url', ['@count' => count($events), '@url' => $calendar_url]);
        
        // Add events to the combined list
        $all_events = array_merge($all_events, $events);
      }
    }
    
    \Drupal::logger('radicale_calendar')->info('Total events from all calendars: @count', ['@count' => count($all_events)]);
    
    if (!empty($all_events)) {
      return new JsonResponse($all_events);
    }
    
    // Return empty array if no events found
    return new JsonResponse([]);
  }

  /**
   * Discover calendar collections using CalDAV PROPFIND.
   */
  private function discoverCalDAVCalendars() {
    $calendars = [];
    
    // Get configuration
    $config = \Drupal::config('radicale_calendar.settings');
    $server_url = $config->get('radicale_server_url') ?: 'http://127.0.0.1:5232';
    $username = $config->get('radicale_username') ?: 'admin';
    
    // CalDAV PROPFIND request to discover calendar collections
    $propfind_url = $server_url . '/' . $username . '/';
    
    $propfind_body = '<?xml version="1.0" encoding="utf-8" ?>
<D:propfind xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:prop>
    <D:resourcetype/>
    <D:displayname/>
    <C:supported-calendar-component-set/>
  </D:prop>
</D:propfind>';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $propfind_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PROPFIND');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/xml',
      'Depth: 1',
      'Content-Length: ' . strlen($propfind_body)
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $propfind_body);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    \Drupal::logger('radicale_calendar')->info('PROPFIND response code: @code', ['@code' => $http_code]);
    
    if ($http_code === 207 && !empty($response)) {
      // Parse the XML response to extract calendar collection URLs
      $calendars = $this->parseCalDAVPropfindResponse($response);
      \Drupal::logger('radicale_calendar')->info('Found @count calendars via PROPFIND', ['@count' => count($calendars)]);
    } else {
      \Drupal::logger('radicale_calendar')->error('PROPFIND failed with code @code', ['@code' => $http_code]);
    }
    
    return $calendars;
  }
  

  /**
   * Parse CalDAV PROPFIND response to extract calendar collection URLs.
   */
  private function parseCalDAVPropfindResponse($xml_response) {
    $calendars = [];
    
    // Use DOMDocument for better XML namespace handling
    $dom = new \DOMDocument();
    $dom->loadXML($xml_response);
    
    // Create XPath object and register namespaces
    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('d', 'DAV:');
    $xpath->registerNamespace('c', 'urn:ietf:params:xml:ns:caldav');
    
    // Find all response elements
    $responses = $xpath->query('//d:response');
    \Drupal::logger('radicale_calendar')->info('Found @count response elements', ['@count' => $responses->length]);
    
    foreach ($responses as $response) {
      // Get the href element
      $href_nodes = $xpath->query('d:href', $response);
      if ($href_nodes->length === 0) {
        continue;
      }
      
      $href = $href_nodes->item(0)->textContent;
      \Drupal::logger('radicale_calendar')->info('Processing href: @href', ['@href' => $href]);
      
      // Skip the parent directory
      if ($href === '/admin/') {
        \Drupal::logger('radicale_calendar')->info('Skipping parent directory');
        continue;
      }
      
      // Check if this is a calendar collection by looking for calendar resourcetype
      $calendar_nodes = $xpath->query('d:propstat/d:prop/d:resourcetype/c:calendar', $response);
      
      if ($calendar_nodes->length > 0) {
        // This is a calendar collection
        $config = \Drupal::config('radicale_calendar.settings');
        $server_url = $config->get('radicale_server_url') ?: 'http://127.0.0.1:5232';
        $full_url = $server_url . $href;
        $calendars[] = $full_url;
        \Drupal::logger('radicale_calendar')->info('Found calendar collection: @url', ['@url' => $full_url]);
      } else {
        \Drupal::logger('radicale_calendar')->info('Not a calendar collection: @href', ['@href' => $href]);
      }
    }
    
    return $calendars;
  }

  /**
   * Fetch events from Radicale CalDAV server.
   */
  private function fetchRadicaleEvents($url, $username, $password) {
    $events = [];
    
    // Basic CalDAV request to get calendar data
    $calendar_url = $url . '/admin/';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $calendar_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PROPFIND');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/xml',
      'Depth: 1',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '<?xml version="1.0" encoding="utf-8" ?>
<D:propfind xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:prop>
    <D:resourcetype/>
    <D:displayname/>
    <C:calendar-data/>
  </D:prop>
</D:propfind>');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 207) {
      // Parse CalDAV response and convert to FullCalendar format
      $events = $this->parseCalDAVResponse($response);
    } else {
      throw new \Exception("CalDAV request failed with HTTP code: $http_code");
    }
    
    return $events;
  }

  /**
   * Parse CalDAV XML response and convert to FullCalendar format.
   */
  private function parseCalDAVResponse($xml_response) {
    $events = [];
    
    // Simple XML parsing - this is a basic implementation
    // In production, you'd want to use a proper CalDAV library
    if (preg_match_all('/BEGIN:VEVENT.*?END:VEVENT/s', $xml_response, $matches)) {
      foreach ($matches[0] as $vevent) {
        $event = $this->parseVEvent($vevent);
        if ($event) {
          $events[] = $event;
        }
      }
    }
    
    return $events;
  }

  /**
   * Parse a single VEVENT from iCalendar data.
   */
  private function parseVEvent($vevent) {
    $event = [];
    
    // Extract SUMMARY (title)
    if (preg_match('/SUMMARY:(.*?)[\r\n]/', $vevent, $matches)) {
      $event['title'] = trim($matches[1]);
    }
    
    // Extract DTSTART (start date)
    if (preg_match('/DTSTART[^:]*:(.*?)[\r\n]/', $vevent, $matches)) {
      $event['start'] = $this->parseCalendarDate(trim($matches[1]));
    }
    
    // Extract DTEND (end date)
    if (preg_match('/DTEND[^:]*:(.*?)[\r\n]/', $vevent, $matches)) {
      $event['end'] = $this->parseCalendarDate(trim($matches[1]));
    }
    
    // Extract DESCRIPTION
    if (preg_match('/DESCRIPTION:(.*?)[\r\n]/', $vevent, $matches)) {
      $event['description'] = trim($matches[1]);
    }
    
    return !empty($event['title']) ? $event : null;
  }

  /**
   * Parse calendar date format to ISO 8601.
   */
  private function parseCalendarDate($date_string) {
    // Handle basic iCalendar date formats
    if (preg_match('/(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})/', $date_string, $matches)) {
      return sprintf('%s-%s-%sT%s:%s:%s', $matches[1], $matches[2], $matches[3], $matches[4], $matches[5], $matches[6]);
    }
    
    return $date_string;
  }

  /**
   * Return sample events for testing.
   */
  private function getSampleEvents() {
    return [
      [
        'title' => 'Sample Event 1',
        'start' => date('Y-m-d') . 'T10:00:00',
        'end' => date('Y-m-d') . 'T11:00:00',
        'description' => 'This is a sample event from your Radicale calendar'
      ],
      [
        'title' => 'Sample Event 2',
        'start' => date('Y-m-d', strtotime('+1 day')) . 'T14:00:00',
        'end' => date('Y-m-d', strtotime('+1 day')) . 'T15:30:00',
        'description' => 'Another sample event'
      ],
    ];
  }

}
