;
; -----------------------------------
;   Template #2: for Passenger list
; -----------------------------------
;
SetDrawColor (64, 64, 64);
SetLineWidth (0.5);
Line (285, 190, 10, 190);
;SetFont ("Arial", "B", 10);                                           
;Text (10, 196, utf8_decode("Manifeste Voyage N° ").$btickets[0]->travel );
SetTextProp ("FOOTTITLE", 10, 274, -1, -1, 0, 0, 0,"Arial", "B", 10);                          
SetTextProp ("FOOTRNB2", 196, 274, -1, -1, 0, 0, 0,"Arial", "I", 9);  
;
;SetTextColor (0, 0, 0);
;SetFont ("Arial", "B", 24);                                           
;Text (110, 15, utf8_decode("Manifeste Voyage N° ").$btickets[0]->travel);
SetTextProp ("TITLE", 55, 15, -1, -1, 0, 0, 0,"Arial", "B", 24);                                      
;
Rect (275, 150, 10, 26, "D");
SetLineWidth (0.10);
SetColumns  ("COLSWDTH", 10, 42, 42, 32, 20, 20);
SetTextProp ("ROW0COL0", 15, 32, -1, 8, 255, 255, 255, "Arial", "B", 11);  
SetTextProp ("ROW1COL0", 15, 44, -1, 6, 0, 0, 0, "Arial", "", 9);  
