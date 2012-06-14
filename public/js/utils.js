//var sDataArray=MultiDimensionalArray(7,2); 
function MultiDimensionalArray(iRows,iCols) 
{ 
    var i; 
    var j; 
    var a = new Array(iRows); 
    for (i=0; i < iRows; i++) 
    { 
        a[i] = new Array(iCols); 
        for (j=0; j < iCols; j++) 
        { 
           a[i][j] = ""; 
        } 
    } 
   return(a); 
} 
