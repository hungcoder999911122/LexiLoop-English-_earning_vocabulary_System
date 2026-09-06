package com.example.englishvocab.controller;

import com.example.englishvocab.dto.ApiDtos.VocabDto;
import com.example.englishvocab.entity.User;
import com.example.englishvocab.service.FavoriteService;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/favorites")
public class FavoriteController {
    private final FavoriteService favoriteService;

    public FavoriteController(FavoriteService favoriteService) {
        this.favoriteService = favoriteService;
    }

    @GetMapping
    public List<VocabDto> list(@AuthenticationPrincipal User user) {
        return favoriteService.list(user);
    }

    @PostMapping("/{vocabId}")
    public Map<String, String> add(@PathVariable Integer vocabId, @AuthenticationPrincipal User user) {
        favoriteService.add(user, vocabId);
        return Map.of("message", "Đã thêm yêu thích");
    }

    @DeleteMapping("/{vocabId}")
    public void remove(@PathVariable Integer vocabId, @AuthenticationPrincipal User user) {
        favoriteService.remove(user, vocabId);
    }
}
