 select setval('beacons_beacon_id_seq', (select max(beacon_id) from beacons));
 select setval('check_in_reasons_check_reason_id_seq', (select max(check_in_reason_id) from check_in_reasons)); 
 select setval('delay_reasons_delay_id_seq', (select max(delay_id) from delay_reasons));
 select setval('devices_device_id_seq', (select max(device_id) from devices));
 select setval('device_vehicles_device_vehicle_id_seq', (select max(device_vehicle_id) from device_vehicles));
 select setval('employee_rosters_employee_roster_id_seq', (select max(employee_roster_id) from employee_rosters));
 select setval('employee_vehicles_employee_vehicle_id_seq', (select max(employee_vehicle_id) from employee_vehicles));
 select setval('fee_items_fee_item_id_seq', (select max(fee_item_id) from fee_items));
 select setval('invoices_invoice_id_seq', (select max(invoice_id) from invoices));
 select setval('migrations_id_seq', (select max(id) from migrations));
 select setval('migrations_id_seq1', (select max(id) from migrations));
 select setval('news_news_id_seq', (select max(news_id) from news));
 select setval('notifications_notification_id_seq', (select max(notification_id) from notifications));
 select setval('oauth_clients_id_seq', (select max(id) from oauth_clients));
 select setval('oauth_personal_access_clients_id_seq', (select max(id) from oauth_personal_access_clients));
 select setval('payments_payment_id_seq', (select max(payment_id) from payments));
 select setval('routes_route_id_seq', (select max(route_id) from routes));
 select setval('route_stoppages_route_stoppage_id_seq', (select max(route_stoppage_id) from route_stoppages));
 select setval('schools_school_id_seq', (select max(school_id) from schools));
 select setval('stoppages_stoppage_id_seq', (select max(stoppage_id) from stoppages));
 select setval('student_absents_student_absent_id_seq', (select max(student_absent_id) from student_absents));
 select setval('student_checkins_student_checkin_id_seq', (select max(student_checkin_id) from student_checkins));
 select setval('student_parents_student_parent_id_seq', (select max(student_parent_id) from student_parents));
 select setval('student_rosters_student_roster_id_seq', (select max(student_roster_id) from student_rosters));
 select setval('students_student_id_seq', (select max(student_id) from students));
 select setval('student_vehicle_routes_student_vehicle_route_id_seq', (select max(student_vehicle_route_id) from student_vehicle_routes));
 select setval('trip_stoppages_trip_stoppage_id_seq', (select max(trip_stoppage_id) from trip_stoppages));
 select setval('trips_trip_id_seq', (select max(trip_id) from trips));
 select setval('users_user_id_seq', (select max(user_id) from users));
 select setval('vehicle_current_location_vehicle_current_location_id_seq', (select max(vehicle_current_location_id) from vehicle_current_location));
 select setval('vehicle_location_history_vehicle_location_history_id_seq', (select max(vehicle_location_history_id) from vehicle_location_history));
 select setval('vehicle_route_schedules_vehicle_route_schedule_id_seq', (select max(vehicle_route_schedule_id) from vehicle_route_schedules));
 select setval('vehicle_routes_vehicle_route_id_seq', (select max(vehicle_route_id) from vehicle_routes)); 
 select setval('vehicles_vehicle_id_seq', (select max(vehicle_id) from vehicles));