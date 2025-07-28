(function (Drupal) {
  'use strict';

  Drupal.behaviors.radicaleCalendar = {
    attach: function (context, settings) {
      // Initialize FullCalendar only once
      const calendarEl = document.getElementById('radicale-calendar');
      
      if (calendarEl && !calendarEl.hasAttribute('data-calendar-initialized')) {
        calendarEl.setAttribute('data-calendar-initialized', 'true');
        
        const calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: 'dayGridMonth',
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
          },
          events: function(fetchInfo, successCallback, failureCallback) {
            // Fetch events from our Drupal API endpoint
            fetch('/calendar/events')
              .then(response => response.json())
              .then(data => {
                console.log('Loaded events from Radicale:', data);
                successCallback(data);
              })
              .catch(error => {
                console.error('Error loading events:', error);
                failureCallback(error);
              });
          },
          eventClick: function(info) {
            // Show comprehensive event details
            let details = 'Event: ' + info.event.title;
            
            // Add location if available
            if (info.event.extendedProps.location) {
              details += '\n\nLocation: ' + info.event.extendedProps.location;
            }
            
            // Add formatted start date and time
            if (info.event.extendedProps.startDate && info.event.extendedProps.startTime) {
              details += '\n\nStart Date: ' + info.event.extendedProps.startDate;
              details += '\nStart Time: ' + info.event.extendedProps.startTime;
            } else if (info.event.start) {
              // Fallback formatting for mm/dd/yyyy and 12-hour time without seconds
              let startDate = new Date(info.event.start);
              let formattedDate = startDate.toLocaleDateString('en-US', {
                month: '2-digit',
                day: '2-digit', 
                year: 'numeric'
              });
              let formattedTime = startDate.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
              });
              details += '\n\nStart Date: ' + formattedDate;
              details += '\nStart Time: ' + formattedTime;
            }
            
            // Add formatted end date and time
            if (info.event.extendedProps.endDate && info.event.extendedProps.endTime) {
              details += '\n\nEnd Date: ' + info.event.extendedProps.endDate;
              details += '\nEnd Time: ' + info.event.extendedProps.endTime;
            } else if (info.event.end) {
              // Fallback formatting for mm/dd/yyyy and 12-hour time without seconds
              let endDate = new Date(info.event.end);
              let formattedDate = endDate.toLocaleDateString('en-US', {
                month: '2-digit',
                day: '2-digit',
                year: 'numeric'
              });
              let formattedTime = endDate.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
              });
              details += '\n\nEnd Date: ' + formattedDate;
              details += '\nEnd Time: ' + formattedTime;
            }
            
            // Add contact information if available
            if (info.event.extendedProps.contact) {
              let contact = info.event.extendedProps.contact;
              details += '\n\nContact Information:';
              
              if (contact.name) {
                details += '\nOrganizer: ' + contact.name;
              }
              if (contact.email) {
                details += '\nEmail: ' + contact.email;
              }
              if (contact.attendee_email) {
                details += '\nAttendee: ' + contact.attendee_email;
                if (contact.attendee_name) {
                  details += ' (' + contact.attendee_name + ')';
                }
              }
              if (contact.contact) {
                details += '\nContact: ' + contact.contact;
              }
              if (contact.url) {
                details += '\nURL: ' + contact.url;
              }
            }
            
            // Add description if available
            if (info.event.extendedProps.description) {
              details += '\n\nDescription: ' + info.event.extendedProps.description;
            }
            
            alert(details);
          },
          eventDidMount: function(info) {
            // Add tooltip or additional styling
            info.el.setAttribute('title', info.event.title);
          },
          loading: function(isLoading) {
            if (isLoading) {
              console.log('Loading events...');
            } else {
              console.log('Events loaded');
            }
          }
        });
        
        calendar.render();
      }
    }
  };

})(Drupal);
