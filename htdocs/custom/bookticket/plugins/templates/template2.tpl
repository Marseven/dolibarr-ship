;
; -----------------------------------
;   Template #2: for employees list
; -----------------------------------
;
SetDrawColor (64, 64, 64);
SetLineWidth (0.5);
Line (10, 190, 285, 190);
SetFont ("Courier", "I", 10);                                           
Text (10, 196, "Employee report");                          
SetTextProp ("FOOTRNB2", 274, 196, -1, -1, 0, 0, 0,"Courier", "I", 9);  
;
SetTextColor (0, 0, 0);
SetFont ("Times", "B", 24);                                           
Text (130, 15, "Employee List");                                      
;
Rect (10, 26, 275, 150, "D");
SetLineWidth (0.15);
SetColumns  ("COLSWDTH", 36, 22, 42, 72, 58, 30);
SetTextProp ("ROW0COL0", 15, 32, -1, 8, 0, 0, 0, "Arial", "I", 11);  
SetTextProp ("ROW1COL0", 15, 44, -1, 6, 0, 0, 0, "Courier", "B", 9);  
