package com.example.englishvocab.controller;

import com.example.englishvocab.dto.ApiDtos.AuthResponse;
import com.example.englishvocab.dto.ApiDtos.LoginRequest;
import com.example.englishvocab.dto.ApiDtos.RegisterRequest;
import com.example.englishvocab.entity.User;
import com.example.englishvocab.service.AuthService;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/auth")
public class AuthController {
    private final AuthService authService;

    public AuthController(AuthService authService) {
        this.authService = authService;
    }

    @PostMapping("/register")
    public AuthResponse register(@RequestBody RegisterRequest req) {
        return authService.register(req);
    }

    @PostMapping("/login")
    public AuthResponse login(@RequestBody LoginRequest req) {
        return authService.login(req);
    }

    @GetMapping("/me")
    public AuthResponse me(@AuthenticationPrincipal User user) {
        return authService.toAuth(user);
    }
}
