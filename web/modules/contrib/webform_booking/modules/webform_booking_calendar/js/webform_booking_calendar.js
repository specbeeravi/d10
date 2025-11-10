(function (Drupal) {
  'use strict';

  Drupal.behaviors.webformBookingCalendar = {
    attach: function (context, settings) {
      if (settings.webformBookingCalendar) {
        Object.keys(settings.webformBookingCalendar).forEach(blockId => {
          const config = settings.webformBookingCalendar[blockId];
          const calendarEl = document.getElementById('calendar-' + blockId);

          console.log('Initializing calendar for block:', blockId);
          console.log('Configuration:', config);

          // Initialize FullCalendar
          const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: function(fetchInfo, successCallback, failureCallback) {
              // Ensure we have valid arrays
              if (!Array.isArray(config.webform_ids) || !Array.isArray(config.element_names) ||
                  !config.webform_ids.length || !config.element_names.length) {
                console.error('Invalid configuration:', config);
                return;
              }

              // Filter out any empty values
              const webform_ids = config.webform_ids.filter(id => id);
              const element_names = config.element_names.filter(name => name);

              // Construct the URL
              const eventsUrl = `/webform-booking-calendar-data/${webform_ids.join(',')}/${element_names.join(',')}`;

              console.log('Fetching events from URL:', eventsUrl);
              console.log('Using webform_ids:', webform_ids);
              console.log('Using element_names:', element_names);

              // Fetch events
              fetch(eventsUrl)
                .then(response => {
                  if (!response.ok) {
                    throw new Error('Network response was not ok');
                  }
                  return response.json();
                })
                .then(data => {
                  console.log('Fetched events:', data);

                  // Map the events directly since they're already in the correct format
                  const updatedData = data.map(event => ({
                    ...event,
                    className: `fc-event-${event.webform_id.replace(/_/g, '-')}`,
                    extendedProps: {
                      details: event.details || 'No additional details available.'
                    }
                  }));

                  console.log('Updated events:', updatedData);
                  successCallback(updatedData);
                })
                .catch(error => {
                  console.error('Error fetching events:', error);
                  failureCallback(error);
                });
            },
            eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
            headerToolbar: {
              left: 'prev,next today',
              center: 'title',
              right: 'dayGridMonth,timeGridWeek,timeGridDay',
            },
            eventClick: info => {
              // Open the URL in a new tab if it exists
              if (info.event.url) {
                window.open(info.event.url, '_blank');
              }
              info.jsEvent.preventDefault();
            },
            eventMouseEnter: info => {
              // Create a tooltip with event details
              const tooltip = document.createElement('div');
              tooltip.className = 'fc-event-tooltip';
              tooltip.style.position = 'absolute';
              tooltip.style.background = '#fff';
              tooltip.style.border = '1px solid #ccc';
              tooltip.style.padding = '5px';
              tooltip.style.borderRadius = '3px';
              tooltip.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
              tooltip.style.whiteSpace = 'pre-wrap';
              tooltip.style.zIndex = '1000';
              tooltip.innerText = info.event.extendedProps.details;

              document.body.appendChild(tooltip);

              // Position the tooltip
              const positionTooltip = event => {
                tooltip.style.left = event.pageX + 10 + 'px';
                tooltip.style.top = event.pageY + 10 + 'px';
              };

              document.addEventListener('mousemove', positionTooltip);

              // Remove the tooltip when the mouse leaves
              const removeTooltip = () => {
                tooltip.remove();
                info.el.removeEventListener('mouseleave', removeTooltip);
                document.removeEventListener('mousemove', positionTooltip);
              };

              info.el.addEventListener('mouseleave', removeTooltip);
            },
            eventMouseLeave: () => {
              // Ensure no lingering tooltips
              const tooltips = document.querySelectorAll('.fc-event-tooltip');
              tooltips.forEach(tooltip => tooltip.remove());
            },
          });

          calendar.render();
        });
      }
    }
  };
})(Drupal);
