function calcHeight() {

    el = document.getElementById('myframe');

    if(el!==null) {
        el.height= 580;
        //find the height of the internal page
        var altura = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
        altura = altura - 200;
        if(altura>=500) {
            // Cambio la altura 
            el.height= altura;
            el.style.height=altura+'px';
        } 
    } 
}

window.onresize = function() { 

   el = document.getElementById('myframe');

   if(el!==null) {
      calcHeight();
   }
}

