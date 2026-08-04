// Validação de formulários
class FormValidator {
  constructor(form) {
    this.form = form;
    this.errors = [];
  }

  validate() {
    this.errors = [];
    const formData = new FormData(this.form);
    
    // Validação específica para cada tipo de usuário
    const userType = formData.get('tipo_usuario');
    
    if (userType === 'professor') {
      this.validateProfessor(formData);
    } else if (userType === 'aluno') {
      this.validateAluno(formData);
    } else if (userType === 'admin') {
      this.validateAdmin(formData);
    }
    
    return this.errors.length === 0;
  }

  validateProfessor(formData) {
    const matricula = formData.get('login_field');
    const senha = formData.get('senha');

    if (!matricula || matricula.trim() === '') {
      this.addError('login_field', 'Número de matrícula é obrigatório');
    } else if (!this.validateMatricula(matricula)) {
      this.addError('login_field', 'Número de matrícula inválido');
    }

    if (!senha || senha.trim() === '') {
      this.addError('senha', 'Senha é obrigatória');
    } else if (senha.length < 6) {
      this.addError('senha', 'Senha deve ter no mínimo 6 caracteres');
    }
  }

  validateAluno(formData) {
    const cpf = formData.get('login_field');
    const senha = formData.get('senha');

    if (!cpf || cpf.trim() === '') {
      this.addError('login_field', 'CPF é obrigatório');
    }
    // Removida validação matemática de CPF - validação será feita apenas no servidor

    if (!senha || senha.trim() === '') {
      this.addError('senha', 'Senha é obrigatória');
    } else if (senha.length < 6) {
      this.addError('senha', 'Senha deve ter no mínimo 6 caracteres');
    }
  }

  validateAdmin(formData) {
    const usuario = formData.get('login_field');
    const senha = formData.get('senha');

    if (!usuario || usuario.trim() === '') {
      this.addError('login_field', 'Usuário é obrigatório');
    } else if (usuario.length < 3) {
      this.addError('login_field', 'Usuário deve ter no mínimo 3 caracteres');
    }

    if (!senha || senha.trim() === '') {
      this.addError('senha', 'Senha é obrigatória');
    } else if (senha.length < 6) {
      this.addError('senha', 'Senha deve ter no mínimo 6 caracteres');
    }
  }

  validateMatricula(matricula) {
    // Valida formato: PROXXXXXX ou apenas números
    const regex = /^(PRO\d{6,}|\d+)$/;
    return regex.test(matricula.trim());
  }

  validateCPF(cpf) {
    cpf = cpf.replace(/[^\d]/g, '');
    
    // Validação básica: apenas verifica se tem 11 dígitos
    if (cpf.length !== 11) return false;
    
    // Valida CPF básica - não rejeita dígitos repetidos para permitir CPF de teste
    if (/^(\d)\1{10}$/.test(cpf)) {
      // Permite CPF de teste para ambiente de desenvolvimento
      return true;
    }
    
    // Validação matemática completa para CPFs reais
    let sum = 0;
    for (let i = 0; i < 9; i++) {
      sum += parseInt(cpf.charAt(i)) * (10 - i);
    }
    let digit = 11 - (sum % 11);
    if (digit >= 10) digit = 0;
    if (digit !== parseInt(cpf.charAt(9))) return false;
    
    sum = 0;
    for (let i = 0; i < 10; i++) {
      sum += parseInt(cpf.charAt(i)) * (11 - i);
    }
    digit = 11 - (sum % 11);
    if (digit >= 10) digit = 0;
    if (digit !== parseInt(cpf.charAt(10))) return false;
    
    return true;
  }

  addError(field, message) {
    this.errors.push({ field, message });
  }

  showErrors() {
    // Remove mensagens de erro anteriores
    this.form.querySelectorAll('.error-message').forEach(el => el.remove());
    this.form.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));

    // Adiciona novas mensagens de erro
    this.errors.forEach(error => {
      const field = this.form.querySelector(`[name="${error.field}"]`);
      if (field) {
        field.classList.add('input-error');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message text-red-500 text-sm mt-1';
        errorDiv.textContent = error.message;
        field.parentNode.appendChild(errorDiv);
      }
    });
  }

  clearErrors() {
    this.form.querySelectorAll('.error-message').forEach(el => el.remove());
    this.form.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
  }
}

// Inicializar validação nos formulários de login
document.addEventListener('DOMContentLoaded', () => {
  const loginForms = document.querySelectorAll('.login-form form');
  
  loginForms.forEach(form => {
    const validator = new FormValidator(form);
    
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      validator.clearErrors();
      
      if (validator.validate()) {
        // Adiciona loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Entrando...';
        
        // Submete o formulário
        form.submit();
      } else {
        validator.showErrors();
      }
    });

    // Validação em tempo real
    form.querySelectorAll('input').forEach(input => {
      input.addEventListener('blur', () => {
        validator.clearErrors();
        validator.validate();
        validator.showErrors();
      });
    });
  });
});
