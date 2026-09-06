package com.example.englishvocab.controller;

import com.example.englishvocab.dto.ApiDtos.ReviewRequest;
import com.example.englishvocab.dto.ApiDtos.ReviewResponse;
import com.example.englishvocab.dto.ApiDtos.VocabDto;
import com.example.englishvocab.entity.User;
import com.example.englishvocab.service.LearningProgressService;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api")
public class LearningProgressController {
    private final LearningProgressService progressService;

    public LearningProgressController(LearningProgressService progressService) {
        this.progressService = progressService;
    }

    @GetMapping("/review/due")
    public List<VocabDto> due(@AuthenticationPrincipal User user) {
        return progressService.dueCards(user);
    }

    @PostMapping("/review")
    public ReviewResponse review(@RequestBody ReviewRequest req, @AuthenticationPrincipal User user) {
        return progressService.review(user, req);
    }

    @GetMapping("/dashboard")
    public Map<String, Object> dashboard(@AuthenticationPrincipal User user) {
        return progressService.dashboard(user);
    }
}
