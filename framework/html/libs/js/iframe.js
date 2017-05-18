function calcHeight()
{
  document.getElementById('myframe').height= 580;
  //find the height of the internal page
  var altura= document.getElementById('myframe').contentWindow.document.body.scrollHeight;

  if(altura>=500) {
    // Cambio la altura 
    document.getElementById('myframe').height= altura;
  } 
}
