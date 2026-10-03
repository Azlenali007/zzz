import Alpine from 'alpinejs';
import { gsap } from 'gsap';
import ApexCharts from 'apexcharts';

window.Alpine = Alpine;
window.gsap = gsap;
window.ApexCharts = ApexCharts;

// Initialize GSAP subtle entrance animations
document.addEventListener('DOMContentLoaded', () => {
  if (typeof gsap !== 'undefined') {
    gsap.from('.gsap-fade-up', {
      duration: 0.6,
      y: 20,
      opacity: 0,
      stagger: 0.08,
      ease: 'power2.out',
    });

    gsap.from('.gsap-fade-in', {
      duration: 0.5,
      opacity: 0,
      ease: 'power1.out',
    });
  }
});

Alpine.start();
