<?php
/**
 * SchedSpot Admin Analytics Dashboard
 *
 * Handles analytics and reporting functionality
 *
 * @package SchedSpot
 * @version 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SchedSpot_Admin_Analytics Class.
 *
 * @class SchedSpot_Admin_Analytics
 * @version 1.0.0
 */
class SchedSpot_Admin_Analytics {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize analytics admin functionality.
     *
     * @since 1.0.0
     */
    public function init() {
        // No hooks needed here - this class is called by SchedSpot_Admin
    }

    /**
     * Analytics page callback.
     *
     * @since 1.0.0
     */
    public function analytics_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'SchedSpot Analytics', 'schedspot' ); ?></h1>
            
            <div class="schedspot-analytics-dashboard">
                <?php $this->render_overview_stats(); ?>
                <?php $this->render_charts_section(); ?>
                <?php $this->render_detailed_reports(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render overview statistics.
     *
     * @since 1.0.0
     */
    private function render_overview_stats() {
        $stats = $this->get_overview_stats();
        ?>
        <div class="analytics-overview">
            <h2><?php _e( 'Overview', 'schedspot' ); ?></h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html( number_format( $stats['total_bookings'] ) ); ?></div>
                        <div class="stat-label"><?php _e( 'Total Bookings', 'schedspot' ); ?></div>
                        <div class="stat-change <?php echo $stats['bookings_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $stats['bookings_change'] >= 0 ? '+' : ''; ?><?php echo esc_html( $stats['bookings_change'] ); ?>%
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <div class="stat-number">$<?php echo esc_html( number_format( $stats['total_revenue'], 2 ) ); ?></div>
                        <div class="stat-label"><?php _e( 'Total Revenue', 'schedspot' ); ?></div>
                        <div class="stat-change <?php echo $stats['revenue_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $stats['revenue_change'] >= 0 ? '+' : ''; ?><?php echo esc_html( $stats['revenue_change'] ); ?>%
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html( number_format( $stats['active_workers'] ) ); ?></div>
                        <div class="stat-label"><?php _e( 'Active Workers', 'schedspot' ); ?></div>
                        <div class="stat-change <?php echo $stats['workers_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $stats['workers_change'] >= 0 ? '+' : ''; ?><?php echo esc_html( $stats['workers_change'] ); ?>%
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html( number_format( $stats['completion_rate'], 1 ) ); ?>%</div>
                        <div class="stat-label"><?php _e( 'Completion Rate', 'schedspot' ); ?></div>
                        <div class="stat-change <?php echo $stats['completion_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $stats['completion_change'] >= 0 ? '+' : ''; ?><?php echo esc_html( $stats['completion_change'] ); ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .analytics-overview {
            margin-bottom: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-icon {
            font-size: 2.5em;
            opacity: 0.8;
        }
        .stat-content {
            flex: 1;
        }
        .stat-number {
            font-size: 2em;
            font-weight: 700;
            color: #0073aa;
            line-height: 1;
        }
        .stat-label {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }
        .stat-change {
            font-size: 0.85em;
            font-weight: 600;
            margin-top: 5px;
        }
        .stat-change.positive { color: #46b450; }
        .stat-change.negative { color: #dc3232; }
        </style>
        <?php
    }

    /**
     * Render charts section.
     *
     * @since 1.0.0
     */
    private function render_charts_section() {
        ?>
        <div class="analytics-charts">
            <h2><?php _e( 'Performance Charts', 'schedspot' ); ?></h2>
            
            <div class="charts-grid">
                <div class="chart-container">
                    <h3><?php _e( 'Bookings Over Time', 'schedspot' ); ?></h3>
                    <div class="chart-placeholder">
                        <canvas id="bookings-chart" width="400" height="200"></canvas>
                        <p class="chart-fallback"><?php _e( 'Chart visualization will be implemented with Chart.js', 'schedspot' ); ?></p>
                    </div>
                </div>

                <div class="chart-container">
                    <h3><?php _e( 'Revenue Trends', 'schedspot' ); ?></h3>
                    <div class="chart-placeholder">
                        <canvas id="revenue-chart" width="400" height="200"></canvas>
                        <p class="chart-fallback"><?php _e( 'Chart visualization will be implemented with Chart.js', 'schedspot' ); ?></p>
                    </div>
                </div>

                <div class="chart-container">
                    <h3><?php _e( 'Service Popularity', 'schedspot' ); ?></h3>
                    <div class="chart-placeholder">
                        <canvas id="services-chart" width="400" height="200"></canvas>
                        <p class="chart-fallback"><?php _e( 'Chart visualization will be implemented with Chart.js', 'schedspot' ); ?></p>
                    </div>
                </div>

                <div class="chart-container">
                    <h3><?php _e( 'Worker Performance', 'schedspot' ); ?></h3>
                    <div class="chart-placeholder">
                        <canvas id="workers-chart" width="400" height="200"></canvas>
                        <p class="chart-fallback"><?php _e( 'Chart visualization will be implemented with Chart.js', 'schedspot' ); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .analytics-charts {
            margin-bottom: 30px;
        }
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .chart-container {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .chart-container h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .chart-placeholder {
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .chart-fallback {
            color: #666;
            font-style: italic;
            text-align: center;
        }
        </style>
        <?php
    }

    /**
     * Render detailed reports section.
     *
     * @since 1.0.0
     */
    private function render_detailed_reports() {
        $reports = $this->get_detailed_reports();
        ?>
        <div class="analytics-reports">
            <h2><?php _e( 'Detailed Reports', 'schedspot' ); ?></h2>
            
            <div class="reports-grid">
                <div class="report-section">
                    <h3><?php _e( 'Top Services', 'schedspot' ); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e( 'Service', 'schedspot' ); ?></th>
                                <th><?php _e( 'Bookings', 'schedspot' ); ?></th>
                                <th><?php _e( 'Revenue', 'schedspot' ); ?></th>
                                <th><?php _e( 'Avg Rating', 'schedspot' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $reports['top_services'] as $service ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $service['name'] ); ?></td>
                                    <td><?php echo esc_html( $service['bookings'] ); ?></td>
                                    <td>$<?php echo esc_html( number_format( $service['revenue'], 2 ) ); ?></td>
                                    <td><?php echo esc_html( number_format( $service['rating'], 1 ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="report-section">
                    <h3><?php _e( 'Top Workers', 'schedspot' ); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e( 'Worker', 'schedspot' ); ?></th>
                                <th><?php _e( 'Bookings', 'schedspot' ); ?></th>
                                <th><?php _e( 'Revenue', 'schedspot' ); ?></th>
                                <th><?php _e( 'Rating', 'schedspot' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $reports['top_workers'] as $worker ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $worker['name'] ); ?></td>
                                    <td><?php echo esc_html( $worker['bookings'] ); ?></td>
                                    <td>$<?php echo esc_html( number_format( $worker['revenue'], 2 ) ); ?></td>
                                    <td><?php echo esc_html( number_format( $worker['rating'], 1 ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="report-section">
                    <h3><?php _e( 'Recent Activity', 'schedspot' ); ?></h3>
                    <div class="activity-list">
                        <?php foreach ( $reports['recent_activity'] as $activity ) : ?>
                            <div class="activity-item">
                                <div class="activity-icon"><?php echo esc_html( $activity['icon'] ); ?></div>
                                <div class="activity-content">
                                    <div class="activity-text"><?php echo esc_html( $activity['text'] ); ?></div>
                                    <div class="activity-time"><?php echo esc_html( $activity['time'] ); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="report-section">
                    <h3><?php _e( 'Export Options', 'schedspot' ); ?></h3>
                    <div class="export-options">
                        <p><?php _e( 'Export your data for further analysis:', 'schedspot' ); ?></p>
                        <div class="export-buttons">
                            <button class="button button-primary" onclick="exportData('bookings')"><?php _e( 'Export Bookings', 'schedspot' ); ?></button>
                            <button class="button button-primary" onclick="exportData('revenue')"><?php _e( 'Export Revenue', 'schedspot' ); ?></button>
                            <button class="button button-primary" onclick="exportData('workers')"><?php _e( 'Export Workers', 'schedspot' ); ?></button>
                            <button class="button button-primary" onclick="exportData('services')"><?php _e( 'Export Services', 'schedspot' ); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .analytics-reports {
            margin-bottom: 30px;
        }
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .report-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .report-section h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .activity-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .activity-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            font-size: 1.2em;
            width: 30px;
            text-align: center;
        }
        .activity-content {
            flex: 1;
        }
        .activity-text {
            font-size: 0.9em;
            color: #333;
        }
        .activity-time {
            font-size: 0.8em;
            color: #666;
            margin-top: 2px;
        }
        .export-options {
            text-align: center;
        }
        .export-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }
        .export-buttons .button {
            flex: 1;
            min-width: 120px;
        }
        </style>

        <script>
        function exportData(type) {
            // This would implement actual data export functionality
            alert('Export ' + type + ' data - functionality to be implemented');
        }
        </script>
        <?php
    }

    /**
     * Get overview statistics.
     *
     * @since 1.0.0
     * @return array Statistics data.
     */
    private function get_overview_stats() {
        // This would fetch real data from the database
        // For now, returning sample data
        return array(
            'total_bookings' => 1247,
            'bookings_change' => 12.5,
            'total_revenue' => 45678.90,
            'revenue_change' => 8.3,
            'active_workers' => 23,
            'workers_change' => 4.2,
            'completion_rate' => 94.7,
            'completion_change' => 2.1,
        );
    }

    /**
     * Get detailed reports data.
     *
     * @since 1.0.0
     * @return array Reports data.
     */
    private function get_detailed_reports() {
        // This would fetch real data from the database
        // For now, returning sample data
        return array(
            'top_services' => array(
                array( 'name' => 'House Cleaning', 'bookings' => 156, 'revenue' => 7800.00, 'rating' => 4.8 ),
                array( 'name' => 'Lawn Care', 'bookings' => 134, 'revenue' => 6700.00, 'rating' => 4.6 ),
                array( 'name' => 'Handyman', 'bookings' => 98, 'revenue' => 4900.00, 'rating' => 4.7 ),
                array( 'name' => 'Pet Sitting', 'bookings' => 87, 'revenue' => 2610.00, 'rating' => 4.9 ),
                array( 'name' => 'Tutoring', 'bookings' => 76, 'revenue' => 3800.00, 'rating' => 4.5 ),
            ),
            'top_workers' => array(
                array( 'name' => 'John Smith', 'bookings' => 45, 'revenue' => 2250.00, 'rating' => 4.9 ),
                array( 'name' => 'Sarah Johnson', 'bookings' => 38, 'revenue' => 1900.00, 'rating' => 4.8 ),
                array( 'name' => 'Mike Wilson', 'bookings' => 34, 'revenue' => 1700.00, 'rating' => 4.7 ),
                array( 'name' => 'Lisa Brown', 'bookings' => 31, 'revenue' => 1550.00, 'rating' => 4.8 ),
                array( 'name' => 'David Lee', 'bookings' => 28, 'revenue' => 1400.00, 'rating' => 4.6 ),
            ),
            'recent_activity' => array(
                array( 'icon' => '📅', 'text' => 'New booking created by Jane Doe', 'time' => '2 minutes ago' ),
                array( 'icon' => '✅', 'text' => 'Booking #1234 completed by John Smith', 'time' => '15 minutes ago' ),
                array( 'icon' => '💰', 'text' => 'Payment received for booking #1233', 'time' => '1 hour ago' ),
                array( 'icon' => '👤', 'text' => 'New worker registered: Alex Turner', 'time' => '2 hours ago' ),
                array( 'icon' => '⭐', 'text' => 'New 5-star review received', 'time' => '3 hours ago' ),
            ),
        );
    }
}
