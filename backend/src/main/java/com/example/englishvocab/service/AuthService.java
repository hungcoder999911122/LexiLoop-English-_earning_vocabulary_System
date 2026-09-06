package com.example.englishvocab.service;

import com.example.englishvocab.dto.ApiDtos.AuthResponse;
import com.example.englishvocab.dto.ApiDtos.LoginRequest;
import com.example.englishvocab.dto.ApiDtos.RegisterRequest;
import com.example.englishvocab.entity.Role;
import com.example.englishvocab.entity.User;
import com.example.englishvocab.repository.RoleRepository;
import com.example.englishvocab.repository.UserRepository;
import com.example.englishvocab.security.JwtUtil;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

@Service
public class AuthService {
    private final UserRepository userRepository;
    private final RoleRepository roleRepository;
    private final PasswordEncoder passwordEncoder;
    private final JwtUtil jwtUtil;

    public AuthService(UserRepository userRepository, RoleRepository roleRepository,
                       PasswordEncoder passwordEncoder, JwtUtil jwtUtil) {
        this.userRepository = userRepository;
        this.roleRepository = roleRepository;
        this.passwordEncoder = passwordEncoder;
        this.jwtUtil = jwtUtil;
    }

    @Transactional
    public AuthResponse register(RegisterRequest req) {
        if (req.userName() == null || req.userName().isBlank() || req.password() == null || req.password().length() < 6) {
            throw new IllegalArgumentException("Username và mật khẩu (≥6 ký tự) là bắt buộc");
        }
        if (userRepository.existsByUserName(req.userName()) || userRepository.existsByEmail(req.email())) {
            throw new IllegalStateException("Username hoặc email đã tồn tại");
        }
        Role learner = roleRepository.findByRoleName("LEARNER")
                .orElseThrow(() -> new IllegalStateException("Thiếu role LEARNER trong CSDL"));
        User u = new User();
        u.setUserName(req.userName().trim());
        u.setEmail(req.email().trim());
        u.setPasswordHash(passwordEncoder.encode(req.password()));
        u.setFullName(req.fullName());
        u.setRole(learner);
        u.setActive(true);
        userRepository.save(u);
        return toAuth(u);
    }

    public AuthResponse login(LoginRequest req) {
        User u = userRepository.findByUserName(req.userName())
                .or(() -> userRepository.findByEmail(req.userName()))
                .orElseThrow(() -> new IllegalArgumentException("Sai tài khoản hoặc mật khẩu"));
        if (!Boolean.TRUE.equals(u.getActive()) || !passwordEncoder.matches(req.password(), u.getPasswordHash())) {
            throw new IllegalArgumentException("Sai tài khoản hoặc mật khẩu");
        }
        return toAuth(u);
    }

    public AuthResponse toAuth(User u) {
        String token = jwtUtil.generate(u.getUserID(), u.getUserName(), u.getRole().getRoleName());
        return new AuthResponse(token, u.getUserID(), u.getUserName(), u.getFullName(), u.getRole().getRoleName());
    }
}
