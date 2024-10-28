
<div class="layout">
    <div class="layout-row">
        <div class="layout-cell">
            <div id="calendar"></div>
        </div>
    </div>
</div>

<script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');

            if (!calendarEl) {
                console.error('Calendar element not found');
                return;
            }

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                
                
                events: {
                    url: '/adminde/mk3d/booking/reservations/getReservations',
                    method: 'GET'
                },         

                eventDidMount: function(info) {
                    console.log('Event rendered:', info.event);
                    tippy(info.el, {
                        content: info.event.extendedProps.description,
                        placement: 'top',
                        trigger: 'mouseenter',
                        theme: 'light'
                    });
                },
                eventClick: function(info) {
                    if (info.event.url) {
                        window.location.href = info.event.url;
                        info.jsEvent.preventDefault(); // don't let the browser navigate
                    }
                }
            });

            calendar.render();
        });
    </script>