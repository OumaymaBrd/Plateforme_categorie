const lockIcon = document.getElementById('lockIcon');
const particlesContainer = document.getElementById('particles');

function createParticle() {
    const particle = document.createElement('span');
    particle.classList.add('particle');
    
    const size = Math.random() * 5 + 5;
    particle.style.width = `${size}px`;
    particle.style.height = `${size}px`;
    
    particle.style.left = `${Math.random() * 100}%`;
    particle.style.top = `${Math.random() * 100}%`;
    
    const duration = Math.random() * 2 + 1;
    particle.style.animation = `float ${duration}s ease-in-out infinite`;
    particle.style.animationDelay = `${Math.random() * 2}s`;
    
    particlesContainer.appendChild(particle);
    
    setTimeout(() => {
        particle.remove();
    }, duration * 1000);
}

setInterval(createParticle, 300);

document.querySelector('.btn').addEventListener('mouseenter', function(e) {
    let x = e.clientX - e.target.offsetLeft;
    let y = e.clientY - e.target.offsetTop;
    let ripple = document.createElement('span');
    ripple.style.left = `${x}px`;
    ripple.style.top = `${y}px`;
    this.appendChild(ripple);
    setTimeout(() => {
        ripple.remove();
    }, 1000);
});