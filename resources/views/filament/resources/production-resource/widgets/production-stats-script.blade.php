<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to extract data from notifications
        function handleNotifications() {
            // Listen for all notifications
            document.addEventListener('notificationDisplayed', function(event) {
                // Check if this is our special notification
                if (event.detail && event.detail.notification && event.detail.notification.title === 'refresh-stats-data') {
                    try {
                        // Parse the filter data from the notification body
                        const filterData = JSON.parse(event.detail.notification.body);
                        
                        // Get all instances of the ProductionStats widget
                        const widgets = document.querySelectorAll('[wire\\:id]');
                        widgets.forEach(widget => {
                            // Find the right Livewire component
                            const componentId = widget.getAttribute('wire:id');
                            if (componentId && widget.getAttribute('wire:key') && 
                                widget.getAttribute('wire:key').includes('production-stats')) {
                                // Update the widget with the new filter data
                                Livewire.find(componentId).set('tableFilteredFrom', filterData.from);
                                Livewire.find(componentId).set('tableFilteredUntil', filterData.until);
                                // Refresh the widget
                                Livewire.find(componentId).call('refresh');
                            }
                        });
                        
                        // Hide the notification since it's just for communication
                        document.querySelectorAll('.fi-notification')
                            .forEach(notification => {
                                if (notification.textContent.includes('refresh-stats-data')) {
                                    notification.style.display = 'none';
                                }
                            });
                            
                    } catch (e) {
                        console.error('Error processing notification data:', e);
                    }
                }
            });
        }
    
        // Initialize notification handling
        handleNotifications();
    });
    </script>