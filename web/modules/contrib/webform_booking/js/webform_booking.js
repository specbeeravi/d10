(function (Drupal, once) {

  // Convert a UTC date to the local timezone.
  function toLocalDate(utcDate) {
    return new Date(utcDate.getTime() + (utcDate.getTimezoneOffset() * 60000));
  }

  Drupal.behaviors.WebformBooking = {
    attach: function (context, settings) {
      once('webform-booking-init', 'body', context).forEach(function () {
        // Initialize total price for the form
        window.webformBookingTotalPrice = 0;
      });

      once('webform-booking', '[id^="appointment-wrapper-"]', context).forEach(function (wrapper) {
        if (settings.webform_booking && settings.webform_booking.elements) {
          Object.keys(settings.webform_booking.elements).forEach(function (key) {
            const elementConfig = settings.webform_booking.elements[key];
            const elementId = elementConfig.elementId;


            if (wrapper.id === `appointment-wrapper-${elementId}`) {
              // Highlight element if it has errors.
              if (elementConfig.hasError !== undefined) {
                wrapper.className = 'webform-booking-error';
              }

              const required = elementConfig.required;
              const formId = elementConfig.formId;
              let startDate = elementConfig.startDate || new Date().toISOString().split('T')[0];
              const endDate = elementConfig.endDate;
              const noSlots = elementConfig.noSlots ?? 'No slots available';
              const defaultPrice = elementConfig.defaultPrice;
              const currency = elementConfig.currency ?? 'USD';
              const maxSeatsPerBooking = elementConfig.maxSeatsPerBooking || 1;
              const paypalEnabled = elementConfig.paypalEnabled || false;
              const dateLabel = elementConfig.dateLabel || '';
              const slotLabel = elementConfig.slotLabel || '';
              const seatsLabel = elementConfig.seatsLabel || '';

              const today = new Date().toISOString().split('T')[0];
              if (new Date(startDate) < new Date(today)) {
                startDate = today;
              }

              function prepareElement() {
                const wrapper = document.getElementById(`appointment-wrapper-${elementId}`);
                if (!wrapper) return;

                // Clear existing content
                wrapper.innerHTML = '';

                // Calendar wrapper
                const calendarWrapper = document.createElement('div');
                calendarWrapper.id = `calendar-wrapper-${elementId}`;
                calendarWrapper.className = 'webform-booking-calendar-wrapper';

                if (dateLabel) {
                  const calendarLabel = document.createElement('label');
                  calendarLabel.textContent = dateLabel;
                  calendarLabel.className = 'webform-booking-label';
                  calendarWrapper.appendChild(calendarLabel);
                }

                const calendarContainer = document.createElement('div');
                calendarContainer.id = `calendar-container-${elementId}`;
                calendarContainer.className = 'webform-booking-calendar-container';
                calendarWrapper.appendChild(calendarContainer);

                wrapper.appendChild(calendarWrapper);

                // Slots wrapper
                const slotsWrapper = document.createElement('div');
                slotsWrapper.id = `slots-wrapper-${elementId}`;
                slotsWrapper.className = 'webform-booking-slots-wrapper';

                if (slotLabel) {
                  const slotsLabel = document.createElement('label');
                  slotsLabel.textContent = slotLabel;
                  slotsLabel.className = 'webform-booking-label';
                  slotsWrapper.appendChild(slotsLabel);
                }

                const slotsContainer = document.createElement('div');
                slotsContainer.id = `slots-container-${elementId}`;
                slotsContainer.className = 'webform-booking-slots-container';
                slotsWrapper.appendChild(slotsContainer);

                wrapper.appendChild(slotsWrapper);

                // Seats wrapper
                const seatsWrapper = document.createElement('div');
                seatsWrapper.id = `seats-wrapper-${elementId}`;
                seatsWrapper.className = 'webform-booking-seats-wrapper';

                const seatsContainer = document.createElement('div');
                seatsContainer.id = `seats-dropdown-container-${elementId}`;
                seatsContainer.className = 'webform-booking-seats-container';
                seatsWrapper.appendChild(seatsContainer);

                wrapper.appendChild(seatsWrapper);

                if (paypalEnabled) {
                  const priceDisplay = document.createElement('div');
                  priceDisplay.id = `price-display-${elementId}`;
                  priceDisplay.className = 'webform-booking-price-display';
                  wrapper.appendChild(priceDisplay);
                }
              }

              function checkAvailableMonthsAndFetchDays() {
                // Get dates in UTC to avoid timezone issues.
                const currentUTCDate = new Date();
                currentUTCDate.setUTCHours(0, 0, 0, 0);
                
                // Convert start date to UTC.
                let start = new Date(startDate);
                start.setUTCHours(0, 0, 0, 0);
                
                // Convert end date to UTC.
                let end = endDate ? new Date(endDate) : new Date(currentUTCDate.getFullYear() + 1, currentUTCDate.getMonth(), currentUTCDate.getDate());
                end.setUTCHours(23, 59, 59, 999);

                // Do not modify start/end dates based on current date.
                // This ensures we show all months in the configured date range.
                const availableMonths = [];
                const requests = [];

                // Start from the first day of the start date's month
                let currentMonth = new Date(start.getFullYear(), start.getMonth(), 1);
                currentMonth.setUTCHours(0, 0, 0, 0);

                // Iterate until we reach the end date's month.
                const endMonth = new Date(end.getFullYear(), end.getMonth(), 1);
                
                while (currentMonth <= endMonth) {
                  const monthStart = formatDate(currentMonth);
                  const daysUrl = Drupal.url(`get-days/${formId}/${elementId}/${monthStart}`);
                  
                  requests.push(
                    fetch(daysUrl)
                      .then(response => {
                        if (!response.ok) {
                          throw new Error(`Failed to fetch for ${monthStart}`);
                        }
                        return response.json();
                      })
                      .catch(error => {
                        console.error('Fetch error:', error);
                        return [];
                      })
                  );
                  
                  availableMonths.push(monthStart);
                  
                  // Move to first day of next month.
                  currentMonth.setMonth(currentMonth.getMonth() + 1);
                }

                Promise.all(requests).then(responses => {
                  const filteredMonths = availableMonths.filter((monthStart, index) => {
                    return responses[index].length > 0;
                  });

                  if (filteredMonths.length > 0) {
                    const initialDate = filteredMonths[0];
                    fetchDays(initialDate, filteredMonths);
                    fetchSlots(initialDate);
                  }
                });
              }

              function fetchDays(date, filteredMonths) {
                const daysUrl = Drupal.url(`get-days/${formId}/${elementId}/${date}`);
                const calendarContainer = document.querySelector(`#calendar-container-${elementId}`);
                const currentDate = toLocalDate(new Date(date));
                const currentYear = currentDate.getFullYear();
                const currentMonth = currentDate.getMonth();
                const slotsContainer = document.getElementById(`slots-container-${elementId}`);
                slotsContainer.removeEventListener('click', fetchSlots);

                fetch(daysUrl)
                  .then(response => {
                    if (!response.ok) {
                      throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                  })
                  .then(function (daysData) {
                    // Show no slots message in slots container instead of replacing everything.
                    if (!daysData || daysData.length === 0) {
                      slotsContainer.innerHTML = `<div class="no-slots-message">${noSlots}</div>`;
                      // Still render the calendar but with no available dates.
                      renderCalendar([], date);
                      return;
                    }

                    renderCalendar(daysData, date);
                    
                    const firstAvailableDay = document.querySelector(`#calendar-container-${elementId} .calendar-day.available`);
                    const firstAvailableDate = firstAvailableDay ? firstAvailableDay.dataset.date : null;
                    if (firstAvailableDate) {
                      fetchSlots(firstAvailableDate);
                    } else {
                      slotsContainer.innerHTML = `<div class="no-slots-message">${noSlots}</div>`;
                    }
                  })
                  .catch(error => {
                    console.error('Error fetching days:', error);
                    slotsContainer.innerHTML = `<div class="error-message">Error loading slots: ${error.message}</div>`;
                  });

                // Helper function to render calendar.
                function renderCalendar(daysData, date) {
                  calendarContainer.innerHTML = createMonthSelect(filteredMonths, date);

                  let weekDaysHtml = '<div class="week-days">';
                  const weekDays = [Drupal.t('Mon'), Drupal.t('Tue'), Drupal.t('Wed'), Drupal.t('Thu'), Drupal.t('Fri'), Drupal.t('Sat'), Drupal.t('Sun')];
                  weekDays.forEach(function (weekDay) {
                    weekDaysHtml += `<div class="week-day">${weekDay}</div>`;
                  });
                  weekDaysHtml += '</div>';
                  calendarContainer.innerHTML += weekDaysHtml;

                  const firstDay = new Date(currentYear, currentMonth, 1).getDay();
                  const emptyDays = (firstDay === 0 ? 6 : firstDay - 1);
                  const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
                  let daysHtml = '<div class="calendar-days">';
                  
                  // Add empty cells for first week.
                  for (let i = 0; i < emptyDays; i++) {
                    daysHtml += '<div class="calendar-day empty"></div>';
                  }

                  // Add calendar days.
                  for (let day = 1; day <= daysInMonth; day++) {
                    const fullDate = `${currentYear}-${(currentMonth + 1).toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
                    let dayClass = 'calendar-day';

                    // Check if the date is in the past.
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    const currentDateObj = new Date(fullDate);
                    if (currentDateObj < today) {
                      dayClass += ' past-date';
                    } else {
                      const dayData = daysData.find(d => d.date === fullDate);
                      if (dayData) {
                        dayClass += ' available';
                        if (!dayData.hasSlots) {
                          dayClass += ' no-slots';
                        }
                      }
                    }

                    daysHtml += `<div class="${dayClass}" data-date="${fullDate}">${day}</div>`;
                  }
                  daysHtml += '</div>';
                  calendarContainer.innerHTML += daysHtml;

                  // Add event listeners for month selection.
                  const monthSelect = document.querySelector(`#month-select-${elementId}`);
                  if (monthSelect) {
                    monthSelect.addEventListener('change', function () {
                      if (this.value) {
                        const selectedMonthYear = this.value.split('-');
                        const year = selectedMonthYear[0];
                        const month = selectedMonthYear[1];
                        resetSlots();
                        fetchDays(`${year}-${month}-01`, filteredMonths);
                      }
                    });
                  }

                  // Add click handlers for available days.
                  const availableDays = document.querySelectorAll(`#calendar-container-${elementId} .calendar-day.available`);
                  availableDays.forEach(function (day) {
                    day.addEventListener('click', function () {
                      document.querySelectorAll(`#appointment-wrapper-${elementId}`).forEach(function (elem) {
                        elem.classList.remove('webform-booking-error');
                      });
                      document.querySelectorAll(`#calendar-container-${elementId} .calendar-day`).forEach(function (elem) {
                        elem.classList.remove('active');
                      });
                      this.classList.add('active');
                      const selectedDate = this.dataset.date;
                      resetSlots();
                      fetchSlots(selectedDate);
                    });
                  });
                }
              }

              function createMonthSelect(filteredMonths, selectedDate) {
                const selected = toLocalDate(new Date(selectedDate));
                let monthSelect = `<select id="month-select-${elementId}">`;

                filteredMonths.forEach(function (monthStart) {
                  const year = new Date(monthStart).getFullYear();
                  const month = new Date(monthStart).getMonth();
                  const optionValue = `${year}-${(month + 1).toString().padStart(2, '0')}`;
                  const isSelected = year === selected.getFullYear() && month === selected.getMonth();
                  const monthName = new Date(year, month).toLocaleString('default', { month: 'long' });
                  monthSelect += `<option value="${optionValue}"${isSelected ? ' selected' : ''}>${monthName} ${year}</option>`;
                });

                monthSelect += '</select>';
                return monthSelect;
              }

              function fetchSlots(date) {
                const slotsUrl = Drupal.url(`get-slots/${formId}/${elementId}/${date}`);
                const slotsContainer = document.getElementById(`slots-container-${elementId}`);
                const noSlotsMessage = `<div class="no-slots-message">${noSlots}</div>`;

                fetch(slotsUrl)
                  .then(response => response.json())
                  .then(function (slotsData) {
                    slotsContainer.innerHTML = '';
                    if (slotsData.every(slot => slot.status === 'unavailable')) {
                      slotsContainer.innerHTML = noSlotsMessage;
                    } else {
                      slotsData.forEach(function (slot) {
                        if (slot.time) {
                          const slotElement = `<div class="calendar-slot ${slot.status}" data-time="${slot.time.split('-')[0]}" data-available-seats="${slot.availableSeats}">${slot.time}</div>`;
                          slotsContainer.innerHTML += slotElement;
                        }
                      });
                      // Trigger custom event 'webform_booking_slots_ready'
                      const event = new CustomEvent('webform_booking_slots_ready', {
                        detail: {
                          formId: formId,
                          elementId: elementId,
                          date: date
                        }
                      });

                      document.dispatchEvent(event);
                      const availableSlots = document.querySelectorAll(`#slots-container-${elementId} .calendar-slot.available`);
                      availableSlots.forEach(function (slot) {
                        slot.addEventListener('click', function () {
                          resetSlots();
                          this.classList.add('selected');
                          const time = this.dataset.time;
                          const availableSeats = parseInt(this.dataset.availableSeats);
                          selectSlot(date, time, availableSeats);
                        });
                      });
                    }
                  });
              }

              function selectSlot(date, time, availableSeats) {
                const slotInput = document.getElementById(`selected-slot-${elementId}`);
                slotInput.value = `${date} ${time}`;
                if (maxSeatsPerBooking !== 1) {
                  createSeatsDropdown(Math.min(maxSeatsPerBooking, availableSeats));
                  const seatsInput = document.getElementById(`seats-${elementId}`);
                  if (seatsInput) {
                    seatsInput.value = 1;
                  }
                }
                updatePriceDisplay();
                updateTotalPrice();
              }

              function createSeatsDropdown(maxSeats) {
                const seatsWrapper = document.getElementById(`seats-wrapper-${elementId}`);
                const seatsContainer = document.getElementById(`seats-dropdown-container-${elementId}`);
                seatsContainer.innerHTML = '';

                const storageKey = `webform-booking-seats-${elementId}`;

                if (maxSeatsPerBooking === 1) {
                  // Remove the label if it exists
                  const existingLabel = seatsWrapper.querySelector('.webform-booking-label');
                  if (existingLabel) {
                    existingLabel.remove();
                  }

                  const hiddenInput = document.createElement('input');
                  hiddenInput.type = 'hidden';
                  hiddenInput.id = `seats-${elementId}`;
                  hiddenInput.name = `${elementId}[seats]`;
                  hiddenInput.value = '1';
                  seatsContainer.appendChild(hiddenInput);
                  localStorage.setItem(storageKey, '1');
                } else {
                  // Add or update the label
                  let seatsLabelElement = seatsWrapper.querySelector('.webform-booking-label');
                  if (!seatsLabelElement) {
                    seatsLabelElement = document.createElement('label');
                    seatsLabelElement.className = 'webform-booking-label';
                    seatsWrapper.insertBefore(seatsLabelElement, seatsContainer);
                  }
                  seatsLabelElement.textContent = seatsLabel || '';

                  const seatsDropdown = document.createElement('select');
                  seatsDropdown.id = `seats-dropdown-${elementId}`;
                  seatsDropdown.name = `seats-dropdown-${elementId}`;
                  seatsDropdown.className = 'webform-booking-seats-dropdown';

                  for (let i = 1; i <= maxSeats; i++) {
                    const option = document.createElement('option');
                    option.value = i;
                    option.textContent = i;
                    seatsDropdown.appendChild(option);
                  }

                  // Set initial value to 1
                  seatsDropdown.value = '1';
                  localStorage.setItem(storageKey, '1');

                  seatsDropdown.addEventListener('change', function () {
                    const seatsInput = document.getElementById(`seats-${elementId}`);
                    if (seatsInput) {
                      seatsInput.value = this.value;
                    }
                    updatePriceDisplay();
                    updateTotalPrice();
                  });

                  seatsContainer.appendChild(seatsDropdown);
                }

                // Trigger initial update
                updatePriceDisplay();
                updateTotalPrice();
              }

              function getTotalPrice() {
                const seatsInput = document.getElementById(`seats-${elementId}`);
                let seats = seatsInput?.value ? parseInt(seatsInput.value, 10) : 1;
                if (isNaN(seats)) seats = 1;
                const price = parseFloat(defaultPrice) || 0;
                const total = (price * seats).toFixed(2);
                return total;
              }

              function updatePriceDisplay() {
                if (!paypalEnabled) return;

                const priceDisplay = document.getElementById(`price-display-${elementId}`);
                if (priceDisplay) {
                  const totalPrice = getTotalPrice();
                  const formattedPrice = isNaN(totalPrice) ? '0.00' : totalPrice;
                  priceDisplay.textContent = `Price: ${getCurrencySymbol(currency)}${formattedPrice}`;
                  priceDisplay.dataset.price = formattedPrice;
                }
              }

              function getCurrencySymbol(currency) {
                const symbols = {
                  'USD': '$',
                  'EUR': '€',
                  'GBP': '£',
                  'AUD': 'A$',
                  'BRL': 'R$',
                  'CAD': 'C$',
                  'CNY': '¥',
                  'CZK': 'Kč',
                  'DKK': 'kr',
                  'HKD': 'HK$',
                  'HUF': 'Ft',
                  'ILS': '₪',
                  'JPY': '¥',
                  'MYR': 'RM',
                  'MXN': 'Mex$',
                  'TWD': 'NT$',
                  'NZD': 'NZ$',
                  'NOK': 'kr',
                  'PHP': '₱',
                  'PLN': 'zł',
                  'SGD': 'S$',
                  'SEK': 'kr',
                  'CHF': 'CHF',
                  'THB': '฿',
                };
                return symbols[currency] || currency + ' ';
              }

              function updateTotalPrice() {
                if (!paypalEnabled) return;

                const allPriceDisplays = document.querySelectorAll('[id^="price-display-"]');
                let total = 0;
                allPriceDisplays.forEach(display => {
                  total += parseFloat(display.dataset.price || 0);
                });
                window.webformBookingTotalPrice = total.toFixed(2);

                // Update or create the total price display
                let totalPriceDisplay = document.getElementById('webform-booking-total-price');
                if (!totalPriceDisplay) {
                  totalPriceDisplay = document.createElement('div');
                  totalPriceDisplay.id = 'webform-booking-total-price';
                  totalPriceDisplay.className = 'webform-booking-total-price';
                  const form = document.querySelector('form.webform-submission-form');
                  if (form) {
                    form.appendChild(totalPriceDisplay);
                  }
                }
                totalPriceDisplay.textContent = `Total Price: ${getCurrencySymbol(currency)}${window.webformBookingTotalPrice}`;

                // Trigger custom event for PayPal integration
                const event = new CustomEvent('webformBookingTotalPriceUpdated', {
                  detail: { totalPrice: window.webformBookingTotalPrice }
                });
                document.dispatchEvent(event);
              }

              function resetSlots() {
                document.getElementById(`selected-slot-${elementId}`).value = '';
                const slots = document.querySelectorAll(`#slots-container-${elementId} .calendar-slot`);
                slots.forEach(function (slot) {
                  slot.classList.remove('selected');
                });
                const seatsContainer = document.getElementById(`seats-dropdown-container-${elementId}`);
                seatsContainer.innerHTML = '';
              }

              function formatDate(date) {
                const year = date.getFullYear();
                const month = (date.getMonth() + 1).toString().padStart(2, '0');
                const day = date.getDate().toString().padStart(2, '0');
                return `${year}-${month}-${day}`;
              }

              prepareElement();
              checkAvailableMonthsAndFetchDays();

              const inputSelector = `#selected-slot-${elementId}`;
              const inputElements = document.querySelectorAll(`input${inputSelector}.required`);
              inputElements.forEach(function (inputElement) {
                inputElement.removeAttribute('required');
                inputElement.removeAttribute('aria-required');
                document.getElementById(`slots-container-${elementId}`).setAttribute('required', 'required');
              });

              const formItem = document.querySelector(`.js-form-item-${elementId}`);
              if (formItem) {
                const observer = new MutationObserver(function (mutations) {
                  mutations.forEach(function (mutation) {
                    if (mutation.attributeName === 'style') {
                      const displayStyle = formItem.style.display;
                      const slotsContainer = document.getElementById(`slots-container-${elementId}`);
                      if (displayStyle === 'block' && required) {
                        slotsContainer.setAttribute('required', 'required');
                        slotsContainer.setAttribute('aria-required', 'true');
                      } else {
                        slotsContainer.removeAttribute('required');
                        slotsContainer.removeAttribute('aria-required');
                      }
                    }
                  });
                });
                observer.observe(formItem, { attributes: true, attributeFilter: ['style'] });
              }
              updateTotalPrice();
            }
          });
        }
      });
    }
  };

  // Add a mutation observer to update the total price when form elements change
  const formObserver = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      if (mutation.type === 'childList' || mutation.type === 'subtree') {
        Drupal.behaviors.WebformBooking.attach(document, drupalSettings);
      }
    });
  });

  const form = document.querySelector('form.webform-submission-form');
  if (form) {
    formObserver.observe(form, { childList: true, subtree: true });
  }

  window.updateTotalPrice = function() {
    let total = 0;
    document.querySelectorAll('[id^="price-display-"]').forEach(function(display) {
      total += parseFloat(display.getAttribute('data-price') || 0);
    });
    window.webformBookingTotalPrice = total.toFixed(2);

    // Update the total price display
    let totalPriceDisplay = document.getElementById('webform-booking-total-price');
    if (totalPriceDisplay) {
      const currency = drupalSettings.webform_booking.currency || 'USD';
      const currencySymbol = getCurrencySymbol(currency);
      totalPriceDisplay.textContent = `Total Price: ${currencySymbol}${window.webformBookingTotalPrice}`;
    }

    // Trigger custom event for PayPal integration
    const event = new CustomEvent('webformBookingTotalPriceUpdated', {
      detail: { totalPrice: window.webformBookingTotalPrice }
    });
    document.dispatchEvent(event);
  };

  function getCurrencySymbol(currency) {
    const symbols = {
      'USD': '$',
      'EUR': '€',
      'GBP': '£',
      'AUD': 'A$',
      'BRL': 'R$',
      'CAD': 'C$',
      'CNY': '¥',
      'CZK': 'Kč',
      'DKK': 'kr',
      'HKD': 'HK$',
      'HUF': 'Ft',
      'ILS': '₪',
      'JPY': '¥',
      'MYR': 'RM',
      'MXN': 'Mex$',
      'TWD': 'NT$',
      'NZD': 'NZ$',
      'NOK': 'kr',
      'PHP': '₱',
      'PLN': 'zł',
      'SGD': 'S$',
      'SEK': 'kr',
      'CHF': 'CHF',
      'THB': '฿',
    };
    return symbols[currency] || currency + ' ';
  }

  // Initial update of total price
  Drupal.behaviors.WebformBooking.attach(document, drupalSettings);

})(Drupal, once);
